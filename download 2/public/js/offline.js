(function(){
  document.getElementById('retryBtn')?.addEventListener('click', function() {
    window.location.reload();
  });
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.ready.then(function(reg) {
      if (reg.active) reg.active.postMessage('check_online');
    });
  }
})();
