document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.run-tool').forEach(function(btn){
    btn.addEventListener('click', function(){
      var file = this.getAttribute('data-file');
      if (!confirm('Run tool: ' + file + '?')) return;
      var out = document.getElementById('tool-output');
      out.textContent = 'Running ' + file + ' ...\n';
      var fd = new FormData();
      fd.append('tool_file', file);
      fd.append('csrf_token', window.csrf_token || document.querySelector('input[name="csrf_token"]')?.value || '');
      fetch('/process.php?action=run_tool', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(j){
          if (j.ok) {
            out.textContent += j.output || '[no output]';
          } else {
            out.textContent += '[error] ' + (j.message || 'Failed');
          }
        }).catch(function(e){ out.textContent += '\nFetch error: '+e; });
    });
  });
});
