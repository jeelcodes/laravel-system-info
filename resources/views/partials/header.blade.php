<div class="titlebar">
  <div class="traffic-lights">
    <span class="tl tl-r"></span>
    <span class="tl tl-y"></span>
    <span class="tl tl-g"></span>
  </div>
  <span class="win-title">{{ config('app.name') }} — system info — bash</span>
  <div class="tab-list">
    <button id="theme-toggle" class="nav-tab" style="padding: 0 12px; margin-right: 8px; font-size: 16px;" onclick="toggleTheme()" title="Toggle Theme">
      <i class="fas fa-sun" id="theme-icon"></i>
    </button>
    <button class="nav-tab active" onclick="showPanel('env', this)">
      <i class="fas fa-terminal"></i> env
    </button>
    <button class="nav-tab" onclick="showPanel('req', this)">
      <i class="fas fa-box"></i> packages
      <span class="badge-dark">{{ count($composerJson['require'] ?? []) }}</span>
    </button>
    <button class="nav-tab" style="display:none;" onclick="showPanel('pkg', this)">
      <i class="fas fa-cubes"></i> packages
      <span class="badge-dark" id="tab-cnt">{{ count($packages) }}</span>
    </button>
  </div>
</div>

<div class="prompt-bar" id="search-bar">
  <span class="prompt-sigil">~/app $</span>
  <input class="prompt-input" id="pkg-search" placeholder="grep package…" autocomplete="off" spellcheck="false">
  <button class="sort-btn" id="sort-az" onclick="toggleSort('az')">
    <i class="fas fa-sort-alpha-down"></i> A–Z
  </button>
  <button class="sort-btn" id="sort-ver" onclick="toggleSort('ver')">
    <i class="fas fa-code-branch"></i> version
  </button>
</div>
