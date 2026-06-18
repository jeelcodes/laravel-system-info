<div class="panel" id="panel-req">
  <div class="tbl-shell">
    <table>
      <thead>
        <tr>
          <th style="width:25%">package</th>
          <th>installed</th>
          <th>latest</th>
          <th style="display:none;">update risk</th>
          <th>vulnerabilities</th>
          <th>issues</th>
          <th title="Calculated based on GitHub stars, open issues ratio, and total Packagist downloads.">health score <i class="fas fa-info-circle" style="font-size: 12px; margin-left: 4px; opacity: 0.7;"></i></th>
          @if($envData['IS_LATEST_LARAVEL'] ?? false)
          <th title="You are already on the latest major version of Laravel.">laravel latest <i class="fas fa-check-circle" style="color:var(--text-ok); font-size:12px; margin-left:4px"></i></th>
          @else
          <th>laravel {{ $envData['NEXT_LARAVEL_VERSION'] ?? '' }} compatible</th>
          @endif
          <th>status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($composerJson['require'] ?? [] as $pkg => $ver)
        @php 
          $installedVer = '-';
          foreach($packages as $p) {
            if ($p['name'] === $pkg) {
              $installedVer = $p['version'];
              break;
            }
          }
        @endphp
        <tr class="req-row" data-pkg="{{ $pkg }}" data-installed="{{ $installedVer }}">
          <td>{{ $pkg }}</td>
          <td>
            <div style="margin-bottom:4px"><span class="ver-tag">{{ $installedVer }}</span></div>
          </td>
          <td class="td-latest"><i class="fas fa-spinner fa-spin" style="color:var(--text-muted)"></i></td>
          <td class="td-risk" style="display:none;"><i class="fas fa-spinner fa-spin" style="color:var(--text-muted)"></i></td>
          <td class="td-vuln"><i class="fas fa-spinner fa-spin" style="color:var(--text-muted)"></i></td>
          <td class="td-issues"><i class="fas fa-spinner fa-spin" style="color:var(--text-muted)"></i></td>
          <td class="td-health"><i class="fas fa-spinner fa-spin" style="color:var(--text-muted)"></i></td>
          <td class="td-laravel-next"><i class="fas fa-spinner fa-spin" style="color:var(--text-muted)"></i></td>
          <td class="td-status"><i class="fas fa-spinner fa-spin" style="color:var(--text-muted)"></i></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
