<?php

namespace Jeelcodes\LaravelSystemInfo\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemInfoController extends Controller
{
    private function fetchJson($url, $params = [], $timeout = 3, $headers = [])
    {
        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'ignore_errors' => true,
                'header' => ''
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];

        if (!isset($headers['User-Agent'])) {
            $headers['User-Agent'] = 'Laravel-System-Info-App';
        }

        foreach ($headers as $k => $v) {
            $opts['http']['header'] .= "$k: $v\r\n";
        }

        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            return ['success' => false, 'data' => null, 'headers' => []];
        }

        $status = 0;
        if (isset($http_response_header[0])) {
            preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
            $status = $match[1] ?? 0;
        }

        $success = $status >= 200 && $status < 300;
        
        $parsedHeaders = [];
        if (!empty($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (strpos($h, ':') !== false) {
                    list($k, $v) = explode(':', $h, 2);
                    $parsedHeaders[strtolower(trim($k))] = trim($v);
                }
            }
        }

        return [
            'success' => $success,
            'data' => $success ? json_decode($result, true) : null,
            'headers' => $parsedHeaders
        ];
    }

    public function index()
    {
        $composerJson = json_decode(
            File::get(\base_path('composer.json')),
            true
        );

        $composerLock = json_decode(
            File::get(\base_path('composer.lock')),
            true
        );

        $packages = $composerLock['packages'] ?? [];

        $laravelMajor = (int) explode('.', \app()->version())[0];
        
        $latestLaravelMajor = Cache::remember('sysinfo_latest_laravel_v2', now()->addHours(24), function () use ($laravelMajor) {
            try {
                $res = $this->fetchJson("https://packagist.org/packages/laravel/framework.json", [], 3);
                if ($res['success']) {
                    $pkgData = $res['data']['package'] ?? [];
                    $versions = array_keys($pkgData['versions'] ?? []);
                    $stableVersions = array_filter($versions, function($v) {
                        return strpos($v, 'dev') === false && strpos($v, 'beta') === false && strpos($v, 'alpha') === false && strpos($v, 'rc') === false;
                    });
                    if (!empty($stableVersions)) {
                        usort($stableVersions, function($a, $b) {
                            return version_compare(ltrim($a, 'vV'), ltrim($b, 'vV'));
                        });
                        return (int) explode('.', ltrim(end($stableVersions), 'v'))[0];
                    }
                }
            } catch (\Exception $e) {}
            return $laravelMajor + 1; // fallback so it doesn't think it's latest
        });

        $envData = [
            'APP_NAME' => \env('APP_NAME'),
            'APP_ENV' => \env('APP_ENV'),
            'APP_DEBUG' => \env('APP_DEBUG'),
            'APP_URL' => \env('APP_URL'),
            'PHP_VERSION' => \phpversion(),
            'LARAVEL_VERSION' => \app()->version(),
            'NEXT_LARAVEL_VERSION' => $laravelMajor + 1,
            'IS_LATEST_LARAVEL' => $laravelMajor >= $latestLaravelMajor,
            'APP_TIMEZONE' => config('app.timezone', \env('APP_TIMEZONE')),
            'DB_CONNECTION' => \env('DB_CONNECTION'),
            'FILESYSTEM_DISK' => \env('FILESYSTEM_DISK'),
            'QUEUE_CONNECTION' => \env('QUEUE_CONNECTION'),
        ];

        return \view(
            'system-info::index',
            \compact(
                'composerJson',
                'packages',
                'envData'
            )
        );
    }

    public function packageDetails(Request $request)
    {
        $packageName = $request->query('package');
        if (!$packageName) {
            return response()->json(['error' => 'Package name required'], 400);
        }

        $installedVersion = $request->query('installed');

        // Changed cache key to v23 to fetch true severity and issues
        $cacheKey = 'sysinfo_pkg_v23_' . md5($packageName . '_' . $installedVersion);
        $backupKey = $cacheKey . '_backup';
        
        $data = Cache::get($cacheKey);

        if (!$data) {
            $result = [
                'latest_version' => '-',
                'vulnerabilities' => [],
                'open_issues' => '-',
                'github_stars' => 0,
                'downloads' => 0,
                'dependency_count' => 0,
                'dependents' => 0,
                'is_used' => true,
                'github_url' => null,
                'homepage_url' => null,
                'health_score' => '-',
                'update_impact' => '-',
                'maintenance_status' => 'Maintained',
                'last_release' => '-',
                'alternative_package' => null,
                'alternative_version' => null,
                'is_compatible' => true,
                'incompatible_reason' => '',
                'next_laravel_compatible' => true,
                'error' => null,
            ];

            try {
                // 1. Packagist info
                $packagistRes = $this->fetchJson("https://packagist.org/packages/{$packageName}.json", [], 5);
                
                if ($packagistRes['success']) {
                    $pkgData = $packagistRes['data']['package'] ?? [];
                    
                    // Latest version
                    $versions = array_keys($pkgData['versions'] ?? []);
                    $stableVersions = array_filter($versions, function($v) {
                        return strpos($v, 'dev') === false && strpos($v, 'beta') === false && strpos($v, 'alpha') === false && strpos($v, 'rc') === false;
                    });
                    
                    if (!empty($stableVersions)) {
                        usort($stableVersions, function($a, $b) {
                            return version_compare(ltrim($a, 'vV'), ltrim($b, 'vV'));
                        });
                        $result['latest_version'] = end($stableVersions);
                        
                        // Calculate Update Impact
                        if ($installedVersion && $installedVersion !== '-' && $result['latest_version'] !== '-') {
                            $v1 = ltrim($installedVersion, 'v');
                            $v2 = ltrim($result['latest_version'], 'v');
                            if ($v1 !== $v2) {
                                $p1 = explode('.', $v1);
                                $p2 = explode('.', $v2);
                                if (isset($p1[0], $p2[0]) && $p1[0] !== $p2[0]) {
                                    $result['update_impact'] = 'Breaking ⚠';
                                } elseif (isset($p1[1], $p2[1]) && $p1[1] !== $p2[1]) {
                                    $result['update_impact'] = 'Minor';
                                } else {
                                    $result['update_impact'] = 'Patch';
                                }
                            } else {
                                $result['update_impact'] = 'Up to date';
                            }
                        }

                        // Check compatibility with Laravel and PHP
                        $latestReqs = $pkgData['versions'][$result['latest_version']]['require'] ?? [];
                        $laravelVer = app()->version();
                        $phpVer = phpversion();
                        
                        $laravelMajor = explode('.', $laravelVer)[0];
                        $nextLaravelMajor = (int)$laravelMajor + 1;
                        preg_match('/^(\d+\.\d+)/', $phpVer, $phpMatches);
                        $phpMajorMinor = $phpMatches[1] ?? '8.0';

                        // Check PHP Compatibility
                        if (isset($latestReqs['php'])) {
                            $phpReq = $latestReqs['php'];
                            preg_match_all('/(?:>|>=|\^|~)?(\d+\.\d+)/', $phpReq, $matches);
                            if (!empty($matches[1])) {
                                $minPhpReq = min($matches[1]);
                                if (version_compare($phpMajorMinor, $minPhpReq, '<')) {
                                    $result['is_compatible'] = false;
                                    $result['incompatible_reason'] = "Requires PHP $phpReq (You have $phpVer)";
                                }
                            }
                        }

                        // Check Laravel Compatibility
                        $laravelReq = $latestReqs['illuminate/support'] ?? $latestReqs['illuminate/contracts'] ?? $latestReqs['laravel/framework'] ?? null;
                        if ($laravelReq) {
                            preg_match_all('/(?:>|>=|\^|~)?(\d+)(?:\.\d+)?/', $laravelReq, $matches);
                            if (!empty($matches[1])) {
                                $allowedMajors = $matches[1];
                                if ($result['is_compatible'] && !in_array($laravelMajor, $allowedMajors)) {
                                    $result['is_compatible'] = false;
                                    $result['incompatible_reason'] = "Requires Laravel $laravelReq (You have Laravel $laravelVer)";
                                }
                                $result['next_laravel_compatible'] = in_array((string)$nextLaravelMajor, $allowedMajors);
                            }
                        }

                        // Calculate Dependency Count (excluding php and extensions)
                        $depCount = 0;
                        foreach ($latestReqs as $reqPkg => $reqConstraint) {
                            if ($reqPkg !== 'php' && strpos($reqPkg, 'ext-') !== 0 && strpos($reqPkg, 'lib-') !== 0) {
                                $depCount++;
                            }
                        }
                        $result['dependency_count'] = $depCount;

                        // Calculate Dependents
                        $result['dependents'] = $pkgData['dependents'] ?? 0;

                        // Check if package is actually used in the codebase
                        $isUsed = false;
                        if (in_array($packageName, ['php', 'laravel/framework']) || strpos($packageName, 'ext-') === 0 || strpos($packageName, 'lib-') === 0) {
                            $isUsed = true;
                        } else {
                            $searchTerms = [];
                            $vendorPath = base_path('vendor/' . $packageName . '/composer.json');
                            if (file_exists($vendorPath)) {
                                $composerData = json_decode(file_get_contents($vendorPath), true);
                                
                                $psr4 = $composerData['autoload']['psr-4'] ?? [];
                                foreach ($psr4 as $namespace => $path) {
                                    $searchTerms[] = rtrim($namespace, '\\');
                                }
                                
                                $aliases = $composerData['extra']['laravel']['aliases'] ?? [];
                                foreach ($aliases as $alias => $class) {
                                    $searchTerms[] = $alias . '::';
                                    $searchTerms[] = 'use ' . $alias . ';';
                                }
                            } else {
                                $parts = explode('/', $packageName);
                                if (isset($parts[1])) $searchTerms[] = $parts[1];
                            }
                            
                            $directories = [base_path('app'), base_path('routes'), base_path('config'), base_path('resources/views')];
                            
                            $allContents = Cache::remember('sysinfo_all_php_contents_v1', now()->addMinutes(5), function() use ($directories) {
                                $text = '';
                                foreach ($directories as $dir) {
                                    if (!is_dir($dir)) continue;
                                    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
                                    foreach ($files as $file) {
                                        if ($file->isFile() && $file->getExtension() === 'php') {
                                            $text .= file_get_contents($file->getPathname()) . "\n";
                                        }
                                    }
                                }
                                return $text;
                            });

                            foreach ($searchTerms as $term) {
                                if (stripos($allContents, $term) !== false) {
                                    $isUsed = true;
                                    break;
                                }
                            }
                        }
                        $result['is_used'] = $isUsed;

                        // Maintenance Status & Last Release
                        if (isset($pkgData['abandoned']) && $pkgData['abandoned']) {
                            $result['maintenance_status'] = 'Deprecated ⚠';
                            
                            // Check if packagist provided a replacement package
                            if (is_string($pkgData['abandoned'])) {
                                $altPkg = $pkgData['abandoned'];
                                $result['alternative_package'] = $altPkg;
                                
                                // Try to fetch the latest version of the alternative package
                                try {
                                    $altRes = $this->fetchJson("https://packagist.org/packages/{$altPkg}.json", [], 3);
                                    
                                    if ($altRes['success']) {
                                        $altData = $altRes['data']['package'] ?? [];
                                        $altVersions = array_keys($altData['versions'] ?? []);
                                        $altStable = array_filter($altVersions, function($v) {
                                            return strpos($v, 'dev') === false && strpos($v, 'beta') === false && strpos($v, 'alpha') === false && strpos($v, 'rc') === false;
                                        });
                                        if (!empty($altStable)) {
                                            usort($altStable, 'version_compare');
                                            $result['alternative_version'] = end($altStable);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    // ignore if we can't fetch alternative package details
                                }
                            }
                        }
                        
                        if (isset($pkgData['versions'][$result['latest_version']]['time'])) {
                            $time = strtotime($pkgData['versions'][$result['latest_version']]['time']);
                            $diff = time() - $time;
                            if ($diff < 86400 * 30) {
                                $result['last_release'] = max(1, round($diff / 86400)) . ' days ago';
                            } elseif ($diff < 86400 * 365) {
                                $result['last_release'] = max(1, round($diff / (86400 * 30))) . ' months ago';
                            } else {
                                $years = round($diff / (86400 * 365));
                                $result['last_release'] = $years . ' years ago ⚠';
                                if ($result['maintenance_status'] === 'Maintained') {
                                    $result['maintenance_status'] = 'Inactive ⚠';
                                    
                                    // Try to find an alternative via Packagist Search since it's inactive
                                    try {
                                        $query = explode('/', $packageName)[1] ?? $packageName;
                                        $searchRes = $this->fetchJson("https://packagist.org/search.json", ['q' => $query], 3);
                                        
                                        if ($searchRes['success']) {
                                            $results = $searchRes['data']['results'] ?? [];
                                            if (is_array($results)) {
                                                foreach ($results as $item) {
                                                    // Don't suggest the exact same package, and ensure the suggested one isn't abandoned
                                                    if ($item['name'] !== $packageName && empty($item['abandoned'])) {
                                                        $altPkg = $item['name'];
                                                        $result['alternative_package'] = $altPkg;
                                                        
                                                        // Fetch the latest version of the alternative package
                                                        $altRes = $this->fetchJson("https://packagist.org/packages/{$altPkg}.json", [], 3);
                                                        
                                                        if ($altRes['success']) {
                                                            $altData = $altRes['data']['package'] ?? [];
                                                            $altVersions = array_keys($altData['versions'] ?? []);
                                                            $altStable = array_filter($altVersions, function($v) {
                                                                return strpos($v, 'dev') === false && strpos($v, 'beta') === false && strpos($v, 'alpha') === false && strpos($v, 'rc') === false;
                                                            });
                                                            if (!empty($altStable)) {
                                                                usort($altStable, 'version_compare');
                                                                $result['alternative_version'] = end($altStable);
                                                            }
                                                        }
                                                        break; // Found our best alternative, stop searching
                                                    }
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        // ignore search failure
                                    }
                                }
                            }
                        }
                    }

                    // Extract GitHub URL and Homepage
                    $repoUrl = $pkgData['repository'] ?? null;
                    if ($repoUrl && strpos($repoUrl, 'github.com') !== false) {
                        $result['github_url'] = $repoUrl;
                    }

                    // Try to find a homepage from the latest version data
                    $result['homepage_url'] = $latestVersionData['homepage'] ?? null;
                    
                    if (isset($pkgData['github_open_issues'])) {
                        $result['open_issues'] = $pkgData['github_open_issues'];
                        
                        if ($result['github_url']) {
                            try {
                                $path = parse_url($result['github_url'], PHP_URL_PATH);
                                $path = trim($path, '/');
                                if (substr($path, -4) === '.git') $path = substr($path, 0, -4);
                                
                                $ghRes = $this->fetchJson("https://api.github.com/repos/{$path}/pulls", [
                                    'state' => 'open',
                                    'per_page' => 1
                                ], 2);
                                
                                if ($ghRes['success']) {
                                    $prCount = 0;
                                    $linkHeader = $ghRes['headers']['link'] ?? null;
                                    if ($linkHeader && preg_match('/&page=(\d+)>; rel="last"/', $linkHeader, $matches)) {
                                        $prCount = (int)$matches[1];
                                    } else {
                                        $prCount = count($ghRes['data'] ?? []);
                                    }
                                    $result['open_issues'] = max(0, $result['open_issues'] - $prCount);
                                }
                            } catch (\Exception $e) {
                                // Fallback to packagist count on rate limit
                            }
                        }
                    }

                    // Calculate Health Score
                    $stars = $pkgData['github_stars'] ?? 0;
                    $result['github_stars'] = $stars;
                    $result['downloads'] = $pkgData['downloads']['total'] ?? 0;
                    
                    $issues = $result['open_issues'] !== '-' ? (int)$result['open_issues'] : 0;
                    
                    if ($stars > 0) {
                        $score = 100;
                        $penalty = min(50, ($issues / $stars) * 1000);
                        $score -= $penalty;
                        $result['health_score'] = (int) max(50, $score);
                    } else {
                        // Fallback health score if github stars are missing
                        $downloads = $pkgData['downloads']['total'] ?? 0;
                        if ($downloads > 10000000) $result['health_score'] = 99;
                        elseif ($downloads > 1000000) $result['health_score'] = 95;
                        elseif ($downloads > 100000) $result['health_score'] = 85;
                        elseif ($downloads > 10000) $result['health_score'] = 70;
                        else $result['health_score'] = 50;
                    }
                } else {
                    $result['maintenance_status'] = 'Private / Custom 🔒';
                    $result['health_score'] = '-';
                    $result['update_impact'] = 'N/A';
                    $result['latest_version'] = $installedVersion && $installedVersion !== '-' ? ltrim($installedVersion, 'v') : 'N/A';
                    $result['is_compatible'] = true;
                    $result['next_laravel_compatible'] = null;
                }

                // 3. Security Advisories
                $advisoriesRes = $this->fetchJson("https://packagist.org/api/security-advisories/", [
                    'packages' => [$packageName]
                ], 5);
                if ($advisoriesRes['success']) {
                    $advs = $advisoriesRes['data']['advisories'][$packageName] ?? null;
                    if ($advs && is_array($advs)) {
                        $result['vulnerabilities'] = array_map(function($a) {
                            $title = strtolower($a['title']);
                            $severity = 'Low';
                            
                            if (isset($a['severity'])) {
                                $severity = ucfirst(strtolower($a['severity']));
                            } else {
                                if (strpos($title, 'critical') !== false || strpos($title, 'remote code execution') !== false || strpos($title, 'rce') !== false) {
                                    $severity = 'Critical';
                                } elseif (strpos($title, 'high') !== false || strpos($title, 'injection') !== false || strpos($title, 'xss') !== false || strpos($title, 'bypass') !== false) {
                                    $severity = 'High';
                                } elseif (strpos($title, 'medium') !== false || strpos($title, 'leakage') !== false || strpos($title, 'disclosure') !== false) {
                                    $severity = 'Medium';
                                }
                            }
                            
                            return ['title' => $a['title'], 'link' => $a['link'] ?? null, 'severity' => $severity];
                        }, $advs);
                    }
                }

            } catch (\Exception $e) {
                $result['error'] = $e->getMessage();
            }

            if (!empty($result['error'])) {
                $backup = Cache::get($backupKey);
                if ($backup) {
                    $data = $backup;
                    // Preserve the error so UI knows it's offline if needed
                    $data['error'] = $result['error'];
                } else {
                    $data = $result;
                }
                // On connection error, cache briefly so it retries soon
                Cache::put($cacheKey, $data, now()->addMinutes(1));
            } else {
                $data = $result;
                Cache::put($cacheKey, $data, now()->addHours(1));
                // Store backup forever for offline use
                Cache::put($backupKey, $data, now()->addYears(1));
            }
        }

        return response()->json($data);
    }
}