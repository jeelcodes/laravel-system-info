<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ config('app.name') }} — system info</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@include('system-info::partials.styles')
</head>
<body>

@include('system-info::partials.header')

<div class="content">
  @include('system-info::partials.env-panel')
  @include('system-info::partials.require-panel')
  @include('system-info::partials.packages-panel')
</div>

<!-- Vulnerability Modal -->
<div id="vulnModal" class="modal" tabindex="-1" style="display:none; position:fixed; z-index:1050; left:0; top:0; width:100%; height:100%; overflow:hidden; outline:0; background-color:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
  <div class="modal-dialog" style="max-width:600px; width:100%; margin:auto; position:relative;">
    <div class="modal-content" style="background-color:var(--bg-surface); color:var(--text-bright); border:1px solid var(--border-color); border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.5);">
      <div class="modal-header" style="border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; padding:1rem;">
        <h5 class="modal-title" style="margin:0; font-size:1.25rem;">Vulnerabilities: <span id="vulnModalPkgName" style="color:var(--text-err)"></span></h5>
        <button type="button" class="close" onclick="closeVulnModal()" style="background:transparent; border:none; font-size:1.5rem; color:var(--text-muted); cursor:pointer; padding:0; margin:0;">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="vulnModalBody" style="padding:1rem; max-height:70vh; overflow-y:auto;">
      </div>
    </div>
  </div>
</div>

@include('system-info::partials.scripts')

</body>
</html>