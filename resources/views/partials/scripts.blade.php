<script>
// Theme Management
function initTheme() {
  const savedTheme = localStorage.getItem('sysinfo-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', savedTheme);
  updateThemeIcon(savedTheme);
}
function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('sysinfo-theme', newTheme);
  updateThemeIcon(newTheme);
}
function updateThemeIcon(theme) {
  const icon = document.getElementById('theme-icon');
  if (icon) {
    icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
  }
}
initTheme();

let activeVendor = null, sortMode = null;
let currentPanel = 'env';
const allRows = [...document.querySelectorAll('#pkg-tbody tr')];
const allReqRows = [...document.querySelectorAll('#panel-req tbody tr')];

// Build vendor chips
const vendors = [...new Set(allRows.map(r => r.dataset.vendor))].sort();
vendors.forEach(v => {
  const el = document.createElement('span');
  el.className = 'vchip'; el.textContent = v;
  el.onclick = () => {
    activeVendor = activeVendor === v ? null : v;
    document.querySelectorAll('.vchip').forEach(c => c.classList.remove('active'));
    if (activeVendor) el.classList.add('active');
    if (currentPanel === 'pkg') applyFilters();
  };
  document.getElementById('vendor-chips').appendChild(el);
});

function toggleSort(mode) {
  sortMode = sortMode === mode ? null : mode;
  document.getElementById('sort-az').classList.toggle('active', sortMode === 'az');
  document.getElementById('sort-ver').classList.toggle('active', sortMode === 'ver');
  applyFilters();
}

function applyFilters() {
  const q = document.getElementById('pkg-search').value.toLowerCase();
  
  if (currentPanel === 'pkg') {
    let rows = allRows.filter(r => {
      const text = r.children[1].textContent.toLowerCase();
      return (!q || text.includes(q)) && (!activeVendor || r.dataset.vendor === activeVendor);
    });
    if (sortMode === 'az')  rows.sort((a,b) => a.children[1].textContent.trim().localeCompare(b.children[1].textContent.trim()));
    if (sortMode === 'ver') rows.sort((a,b) => a.dataset.ver.localeCompare(b.dataset.ver));
    
    allRows.forEach(r => r.style.display = 'none');
    const tbody = document.getElementById('pkg-tbody');
    rows.forEach((r, i) => { r.style.display = ''; r.cells[0].textContent = i + 1; tbody.appendChild(r); });
    
    const txt = rows.length + ' package' + (rows.length !== 1 ? 's' : '');
    document.getElementById('pkg-footer').textContent = txt;
    document.getElementById('tab-cnt').textContent = rows.length;
  } 
  else if (currentPanel === 'req') {
    let rows = allReqRows.filter(r => {
      const text = r.dataset.pkg.toLowerCase();
      return (!q || text.includes(q));
    });
    if (sortMode === 'az')  rows.sort((a,b) => a.dataset.pkg.localeCompare(b.dataset.pkg));
    
    allReqRows.forEach(r => r.style.display = 'none');
    const tbody = document.querySelector('#panel-req tbody');
    rows.forEach(r => { r.style.display = ''; tbody.appendChild(r); });
  }
}

document.getElementById('pkg-search').addEventListener('input', applyFilters);

function showPanel(id, el) {
  currentPanel = id;
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel-' + id).classList.add('active');
  el.classList.add('active');
  
  const bar = document.getElementById('search-bar');
  bar.classList.toggle('visible', id === 'pkg' || id === 'req');
  
  const sortVerBtn = document.getElementById('sort-ver');
  if (sortVerBtn) {
    if (id === 'req') {
      sortVerBtn.style.display = 'none';
      if (sortMode === 'ver') {
        sortMode = null;
        sortVerBtn.classList.remove('active');
      }
    } else {
      sortVerBtn.style.display = '';
    }
  }
  
  if (id === 'pkg' || id === 'req') applyFilters();

  if (id === 'req' && !window.reqLoaded) {
    window.reqLoaded = true;
    loadRequireData();
  }
}

async function loadRequireData() {
  const rows = Array.from(document.querySelectorAll('.req-row'));
  const concurrencyLimit = 5; // Load 5 packages concurrently
  let activePromises = [];
  
  for (let row of rows) {
    const p = fetchRowData(row).finally(() => {
      activePromises = activePromises.filter(prom => prom !== p);
    });
    activePromises.push(p);
    
    if (activePromises.length >= concurrencyLimit) {
      await Promise.race(activePromises);
    }
  }
  await Promise.all(activePromises);
}

async function fetchRowData(row) {
    const pkg = row.dataset.pkg;
    const installed = row.dataset.installed;
    try {
      const res = await fetch(`{{ route('system-info.package-details') }}?package=${encodeURIComponent(pkg)}&installed=${encodeURIComponent(installed)}`);
      const data = await res.json();
      
      
      // Render Stars and Downloads underneath Package Name
      let extraInfoHtml = '';
      
      if (data.github_stars > 0) {
        let starText = data.github_stars;
        if (data.github_stars >= 1000) {
          starText = (data.github_stars / 1000).toFixed(1) + 'k';
        }
        extraInfoHtml += `<span style="margin-right:12px;" title="GitHub Stars"><i class="fas fa-star" style="color:var(--text-warn)"></i> ${starText}</span>`;
      }
      
      if (data.downloads > 0) {
        let dlText = data.downloads;
        if (data.downloads >= 1000000) {
          dlText = (data.downloads / 1000000).toFixed(1) + 'M';
        } else if (data.downloads >= 1000) {
          dlText = (data.downloads / 1000).toFixed(1) + 'k';
        }
        extraInfoHtml += `<span style="margin-right:12px;" title="Total Downloads"><i class="fas fa-arrow-down" style="color:#60a5fa"></i> ${dlText}</span>`;
      }
      
      if (data.dependency_count >= 0) {
        extraInfoHtml += `<span style="margin-right:12px;" title="Dependencies"><i class="fas fa-cubes" style="color:#a855f7"></i> ${data.dependency_count}</span>`;
      }
      
      let pkgDiv = row.querySelector('td:first-child');
      if (extraInfoHtml !== '') {
        pkgDiv.innerHTML += `<div style="font-size:11px; color:var(--text-muted); margin-top:4px; display:flex; align-items:center; flex-wrap:wrap;">${extraInfoHtml}</div>`;
      }


      
      // Latest version
      let releaseLink = '';
      if (data.github_url && data.latest_version !== '-') {
          releaseLink = `<a href="${data.github_url}/releases/tag/${data.latest_version}" target="_blank" style="color:var(--text-muted); margin-left:6px; font-size:12px; transition: color 0.15s;" title="View Release Notes" onmouseover="this.style.color='var(--accent-violet)'" onmouseout="this.style.color='var(--text-muted)'"><i class="far fa-file-alt"></i></a>`;
      }
      
      // Update Impact
      let impactBadge = '';
      if (data.update_impact.includes('Breaking')) {
        impactBadge = `<div style="font-size:10px; color:var(--text-err); margin-top:4px;"><i class="fas fa-exclamation-triangle"></i> Major Update</div>`;
      } else if (data.update_impact === 'Minor') {
        impactBadge = `<div style="font-size:10px; color:var(--text-warn); margin-top:4px;"><i class="fas fa-arrow-up"></i> Minor Update</div>`;
      } else if (data.update_impact === 'Patch') {
        impactBadge = `<div style="font-size:10px; color:var(--text-ok); margin-top:4px;"><i class="fas fa-bug"></i> Patch Update</div>`;
      } else if (data.update_impact === 'Up to date') {
        impactBadge = `<div style="font-size:10px; color:var(--text-muted); margin-top:4px;">Up to date</div>`;
      }

      row.querySelector('.td-latest').innerHTML = `<div style="display:flex; align-items:center"><span class="ver-tag">${data.latest_version}</span>${releaseLink}</div>${data.latest_version !== '-' ? impactBadge : ''}`;
      
      // Compatibility
      let compatBadge = '';
      if (data.is_compatible === false) {
          compatBadge = `<div style="font-size:10px; color:var(--text-err); margin-top:2px" title="${data.incompatible_reason}"><i class="fas fa-ban"></i> Incompatible</div>`;
      } else if (data.is_compatible === true && data.update_impact !== 'Up to date') {
          compatBadge = `<div style="font-size:10px; color:var(--text-ok); margin-top:2px" title="PHP & Laravel check passed"><i class="fas fa-check-circle"></i> Compatible</div>`;
      }
      
      row.querySelector('.td-risk').innerHTML = `${impactBadge}${compatBadge}`;
      
      // Vulnerabilities
      if (data.vulnerabilities && data.vulnerabilities.length > 0) {
        window.pkgVulns = window.pkgVulns || {};
        window.pkgVulns[pkg] = data.vulnerabilities;
        row.querySelector('.td-vuln').innerHTML = `<span class="pill-err" style="cursor:pointer; background-color:rgba(220,38,38,0.2); color:#ef4444; padding:2px 8px; border-radius:12px; font-size:12px; font-weight:bold; display:inline-flex; align-items:center;" onclick="showVulnModal('${pkg}')" title="Click to view vulnerabilities details"><i class="fas fa-shield-alt" style="margin-right:4px;"></i> ${data.vulnerabilities.length}</span>`;
      } else {
        row.querySelector('.td-vuln').innerHTML = `<span class="pill-ok"><i class="fas fa-check"></i> 0</span>`;
      }
      
      // Issues
      if (data.github_url) {
        row.querySelector('.td-issues').innerHTML = `<a href="${data.github_url}/issues" target="_blank" style="color:var(--text-bright)">${data.open_issues} <i class="fas fa-external-link-alt" style="font-size:10px; color:var(--text-muted)"></i></a>`;
      } else {
        row.querySelector('.td-issues').innerHTML = `<span style="color:var(--text-muted)">-</span>`;
      }
      
      // Health Score
      let hcColor = data.health_score > 80 ? 'var(--text-ok)' : (data.health_score > 60 ? 'var(--text-warn)' : 'var(--text-err)');
      let hcText = data.health_score === '-' ? '-' : data.health_score + '/100';
      row.querySelector('.td-health').innerHTML = `<span style="color:${hcColor}; font-weight:bold;">${hcText}</span>`;
      
      // Next Laravel Compatibility
      const isLatestLaravel = {{ ($envData['IS_LATEST_LARAVEL'] ?? false) ? 'true' : 'false' }};
      if (isLatestLaravel) {
        row.querySelector('.td-laravel-next').innerHTML = `<span style="color:var(--text-muted)" title="You are already on the latest Laravel version">N/A</span>`;
      } else {
        if (data.next_laravel_compatible === true) {
          row.querySelector('.td-laravel-next').innerHTML = `<span style="color:var(--text-ok)" title="Compatible with next major version"><i class="fas fa-check"></i> Yes</span>`;
        } else if (data.next_laravel_compatible === false) {
          row.querySelector('.td-laravel-next').innerHTML = `<span style="color:var(--text-err)" title="Not compatible with next major version"><i class="fas fa-times"></i> No</span>`;
        } else {
          row.querySelector('.td-laravel-next').innerHTML = `<span style="color:var(--text-muted)">-</span>`;
        }
      }
      
      // Status & Release
      let statusColor = 'var(--text-ok)';
      if (data.maintenance_status.includes('Inactive') || data.maintenance_status.includes('Deprecated')) statusColor = 'var(--text-err)';
      
      let altPackageHtml = '';
      if (data.alternative_package) {
         let altVerText = data.alternative_version ? ` (<span class="ver-tag" style="padding:0 2px">${data.alternative_version}</span>)` : '';
         altPackageHtml = `<div style="font-size:10px; margin-top:4px; color:var(--text-bright)" title="Recommended replacement"><i class="fas fa-arrow-right"></i> Use: <strong>${data.alternative_package}</strong>${altVerText}</div>`;
      }
      
      row.querySelector('.td-status').innerHTML = `<div style="color:${statusColor}">${data.maintenance_status}</div><div style="font-size:10px; color:var(--text-muted); margin-top:2px">${data.last_release}</div>${altPackageHtml}`;
      
    } catch (e) {
      row.querySelector('.td-latest').innerHTML = '-';
      row.querySelector('.td-risk').innerHTML = '-';
      row.querySelector('.td-vuln').innerHTML = '-';
      row.querySelector('.td-issues').innerHTML = '-';
      row.querySelector('.td-health').innerHTML = '-';
      row.querySelector('.td-laravel-next').innerHTML = '-';
      row.querySelector('.td-status').innerHTML = '-';
    }
}

function showVulnModal(pkg) {
  const vulns = window.pkgVulns && window.pkgVulns[pkg] ? window.pkgVulns[pkg] : [];
  document.getElementById('vulnModalPkgName').textContent = pkg;
  
  let counts = { Critical: 0, High: 0, Medium: 0, Low: 0 };
  let listHtml = '';
  
  vulns.forEach(v => {
    let s = (v.severity || 'Low');
    if(counts[s] !== undefined) counts[s]++;
    else counts['Low']++;
    
    let color = 'var(--text-muted)';
    if (s === 'Critical') color = '#ef4444';
    else if (s === 'High') color = '#f97316';
    else if (s === 'Medium') color = '#eab308';
    else if (s === 'Low') color = '#3b82f6';
    
    listHtml += `
      <div style="border-bottom:1px solid var(--border-color); padding:10px 0;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <strong style="color:var(--text-bright); font-size:14px; flex:1; padding-right:10px;">${v.title}</strong>
          <span style="background-color:${color}; color:#fff; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:bold;">${s}</span>
        </div>
        ${v.link ? `<div style="margin-top:4px;"><a href="${v.link}" target="_blank" style="color:var(--accent-violet); font-size:12px; text-decoration:none;"><i class="fas fa-external-link-alt"></i> View Advisory</a></div>` : ''}
      </div>
    `;
  });
  
  const total = vulns.length;
  
  let summaryHtml = `
    <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
      <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); padding:8px 12px; border-radius:6px; flex:1; text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase;">Total</div>
        <div style="font-size:18px; font-weight:bold; color:var(--text-bright);">${total}</div>
      </div>
      <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); padding:8px 12px; border-radius:6px; flex:1; text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase;">Critical</div>
        <div style="font-size:18px; font-weight:bold; color:#ef4444;">${counts.Critical}</div>
      </div>
      <div style="background:rgba(249,115,22,0.1); border:1px solid rgba(249,115,22,0.2); padding:8px 12px; border-radius:6px; flex:1; text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase;">High</div>
        <div style="font-size:18px; font-weight:bold; color:#f97316;">${counts.High}</div>
      </div>
      <div style="background:rgba(234,179,8,0.1); border:1px solid rgba(234,179,8,0.2); padding:8px 12px; border-radius:6px; flex:1; text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase;">Medium</div>
        <div style="font-size:18px; font-weight:bold; color:#eab308;">${counts.Medium}</div>
      </div>
      <div style="background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2); padding:8px 12px; border-radius:6px; flex:1; text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase;">Low</div>
        <div style="font-size:18px; font-weight:bold; color:#3b82f6;">${counts.Low}</div>
      </div>
    </div>
  `;
  
  document.getElementById('vulnModalBody').innerHTML = summaryHtml + '<div style="margin-top:15px; border-top:1px solid var(--border-color); padding-top:10px;">' + listHtml + '</div>';
  document.getElementById('vulnModal').style.display = 'flex';
}

function closeVulnModal() {
  document.getElementById('vulnModal').style.display = 'none';
}

window.addEventListener('click', function(e) {
  const modal = document.getElementById('vulnModal');
  if (e.target === modal) {
    closeVulnModal();
  }
});
</script>
