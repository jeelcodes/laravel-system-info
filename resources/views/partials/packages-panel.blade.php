<div class="panel" id="panel-pkg">
  <div class="chip-row" id="vendor-chips"></div>
  <div class="tbl-shell">
    <table>
      <thead><tr><th style="width:2.5rem">#</th><th>package</th><th style="width:110px">version</th></tr></thead>
      <tbody id="pkg-tbody">
        @foreach($packages as $i => $pkg)
        @php [$vendor, $name] = array_pad(explode('/', $pkg['name'], 2), 2, $pkg['name']); @endphp
        <tr data-vendor="{{ $vendor }}" data-ver="{{ $pkg['version'] }}">
          <td class="row-num">{{ $i + 1 }}</td>
          <td><span class="vendor-tag">{{ $vendor }}</span>{{ $name }}</td>
          <td><span class="ver-tag">{{ $pkg['version'] }}</span></td>
        </tr>
        @endforeach
      </tbody>
      <tfoot><tr><td colspan="3" id="pkg-footer">{{ count($packages) }} packages</td></tr></tfoot>
    </table>
  </div>
</div>
