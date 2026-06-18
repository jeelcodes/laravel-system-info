<div class="panel active" id="panel-env">
  @if($envData['APP_DEBUG'] ?? false)
  <div class="warn-strip">
    <i class="fas fa-exclamation-triangle"></i>
    <span><strong>APP_DEBUG is true</strong> — disable before deploying or sensitive data may leak.</span>
  </div>
  @endif

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-card-top"><i class="fas fa-id-badge" style="color:#a78bfa"></i><span class="stat-card-label">App name</span></div>
      <div class="stat-card-val">{{ $envData['APP_NAME'] ?? '—' }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-top"><i class="fas fa-leaf" style="color:var(--text-ok)"></i><span class="stat-card-label">Environment</span></div>
      <div class="stat-card-val ok">{{ $envData['APP_ENV'] ?? '—' }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-top"><i class="fab fa-laravel" style="color:var(--text-err)"></i><span class="stat-card-label">Laravel</span></div>
      <div class="stat-card-val">{{ $envData['LARAVEL_VERSION'] ?? '—' }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-top"><i class="fas fa-code" style="color:var(--ver-text)"></i><span class="stat-card-label">PHP</span></div>
      <div class="stat-card-val">{{ $envData['PHP_VERSION'] ?? '—' }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-top"><i class="fas fa-bug" style="color:var(--text-warn)"></i><span class="stat-card-label">Debug</span></div>
      <div class="stat-card-val {{ ($envData['APP_DEBUG'] ?? false) ? 'warn' : 'ok' }}">
        {{ ($envData['APP_DEBUG'] ?? false) ? 'ON' : 'OFF' }}
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-card-top"><i class="fas fa-link" style="color:#60a5fa"></i><span class="stat-card-label">URL</span></div>
      <div class="stat-card-val mono">{{ $envData['APP_URL'] ?? '—' }}</div>
    </div>
  </div>

  <div class="section-divider"><span>runtime config</span><hr></div>
  <div class="kv-grid">
    @foreach($envData as $key => $value)
    @if(!in_array($key, ['NEXT_LARAVEL_VERSION', 'IS_LATEST_LARAVEL']))
    <div class="kv-item">
      <div class="kv-key">{{ $key }}</div>
      <div class="kv-val">
        @if($key === 'APP_ENV')
          <span class="pill-ok">{{ $value }}</span>
        @elseif($key === 'APP_DEBUG' && $value)
          <span class="pill-err">true</span>
        @else
          {{ is_bool($value) ? ($value ? 'true' : 'false') : ($value ?: '—') }}
        @endif
      </div>
    </div>
    @endif
    @endforeach
  </div>
</div>
