// MAIN world script — runs in the page's JS context (not isolated)

(function() {

  function findButton(selector, text) {
    var candidates = document.querySelectorAll(selector || 'button');
    if (text) {
      for (var i = 0; i < candidates.length; i++) {
        if (candidates[i].textContent?.trim() === text) return candidates[i];
      }
      return null;
    }
    return candidates[0] || null;
  }

  // Simple click
  document.addEventListener('lesrats-click', function(e) {
    var el = findButton(e.detail?.selector, e.detail?.text);
    if (!el) { console.warn('🐀 [MAIN] Not found:', e.detail?.selector, e.detail?.text); return; }
    el.click();
    console.log('🐀 [MAIN] Clicked:', el.textContent?.trim());
  });

  // Scroll + click for the Terminé button
  document.addEventListener('lesrats-scroll-and-click', function(e) {
    var el = findButton(e.detail?.selector, e.detail?.text);
    if (!el) { console.warn('🐀 [MAIN] Not found:', e.detail?.selector, e.detail?.text); return; }

    // The footer is OUTSIDE .wt-overlay__modal — they are siblings inside #le-variations-overlay
    // Scroll the overlay container itself
    var overlay = document.querySelector('#le-variations-overlay');
    if (overlay) {
      overlay.scrollTop = overlay.scrollHeight;
      console.log('🐀 [MAIN] Scrolled #le-variations-overlay to bottom');
    }

    // Also scroll every scrollable ancestor of the button
    var parent = el.parentElement;
    while (parent) {
      if (parent.scrollHeight > parent.clientHeight) {
        parent.scrollTop = parent.scrollHeight;
      }
      parent = parent.parentElement;
    }

    el.scrollIntoView({ behavior: 'instant', block: 'center' });

    setTimeout(function() {
      var rect = el.getBoundingClientRect();
      console.log('🐀 [MAIN] Terminé rect: top=' + Math.round(rect.top) + ' bottom=' + Math.round(rect.bottom) + ' visible=' + (rect.height > 0 && rect.width > 0) + ' inViewport=' + (rect.top >= 0 && rect.bottom <= window.innerHeight));

      el.focus();
      el.click();
      console.log('🐀 [MAIN] .click() on Terminé');

      // Also try React props
      try {
        var keys = Object.keys(el);
        var propsKey = keys.find(function(k) { return k.startsWith('__reactProps$'); });
        if (propsKey && el[propsKey] && el[propsKey].onClick) {
          el[propsKey].onClick({ preventDefault: function(){}, stopPropagation: function(){}, nativeEvent: new MouseEvent('click'), target: el, currentTarget: el });
          console.log('🐀 [MAIN] Called __reactProps$.onClick');
        } else {
          console.log('🐀 [MAIN] React keys:', keys.filter(function(k) { return k.startsWith('__'); }).join(', '));
        }
      } catch(err) {
        console.warn('🐀 [MAIN] React error:', err.message);
      }
    }, 400);
  });

  // Dismiss dropdown
  document.addEventListener('lesrats-dismiss-dropdown', function() {
    var heading = document.querySelector('.wt-overlay__modal h1');
    if (heading) {
      var rect = heading.getBoundingClientRect();
      heading.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true, clientX: rect.left + rect.width/2, clientY: rect.top + rect.height/2, button: 0 }));
      if (document.activeElement) document.activeElement.blur();
      console.log('🐀 [MAIN] Dismissed dropdown');
    }
  });

  console.log('🐀 [MAIN] Etsy click helper ready');
})();
