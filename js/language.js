/** Dynamic English/Hindi translation controller. Google UI stays fully hidden. */
(function () {
  const STORAGE_KEY = 'sufia_site_language';

  function installTranslatorHidingStyles() {
    if (document.getElementById('sufia-translator-hide-css')) return;

    const style = document.createElement('style');
    style.id = 'sufia-translator-hide-css';
    style.textContent = `
      /* Keep Google Translate engine available, but never show its own UI. */
      #google_translate_element,
      .goog-te-banner-frame,
      iframe.goog-te-banner-frame,
      .goog-te-gadget,
      .goog-te-gadget-simple,
      .goog-te-menu-frame,
      .goog-te-balloon-frame,
      #goog-gt-tt,
      .goog-tooltip,
      .goog-tooltip:hover,
      .VIpgJd-ZVi9od-ORHb-OEVmcd,
      .VIpgJd-ZVi9od-aZ2wEe-wOHMyf,
      .VIpgJd-yAWNEb-L7lbkb,
      .VIpgJd-ZVi9od-l4eHX-hSRGPd,
      body > .skiptranslate:not(#google_translate_element) {
        display: none !important;
        visibility: hidden !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        pointer-events: none !important;
      }

      html, body {
        top: 0 !important;
        margin-top: 0 !important;
      }

      /* Remove Google hover/highlight decoration from translated text. */
      .goog-text-highlight {
        background: transparent !important;
        box-shadow: none !important;
      }
    `;
    document.head.appendChild(style);
  }

  function hideInjectedGoogleUi() {
    document.documentElement.style.setProperty('top', '0px', 'important');
    document.body && document.body.style.setProperty('top', '0px', 'important');

    const selectors = [
      'iframe.goog-te-banner-frame',
      '.goog-te-banner-frame',
      '.goog-te-menu-frame',
      '.goog-te-balloon-frame',
      '#goog-gt-tt',
      '.VIpgJd-ZVi9od-ORHb-OEVmcd',
      '.VIpgJd-ZVi9od-aZ2wEe-wOHMyf',
      '.VIpgJd-yAWNEb-L7lbkb',
      '.VIpgJd-ZVi9od-l4eHX-hSRGPd'
    ];

    selectors.forEach(selector => {
      document.querySelectorAll(selector).forEach(node => {
        node.style.setProperty('display', 'none', 'important');
        node.style.setProperty('visibility', 'hidden', 'important');
      });
    });
  }

  function setTranslateCookie(lang) {
    const value = lang === 'hi' ? '/en/hi' : '/en/en';
    document.cookie = `googtrans=${value};path=/`;

    // Do not write a domain cookie on localhost/file previews.
    if (location.hostname && location.hostname.includes('.')) {
      document.cookie = `googtrans=${value};path=/;domain=${location.hostname}`;
    }
  }

  function syncGoogleCombo(lang) {
    const combo = document.querySelector('.goog-te-combo');
    if (!combo) return false;

    combo.value = lang === 'hi' ? 'hi' : 'en';
    combo.dispatchEvent(new Event('change', { bubbles: true }));
    hideInjectedGoogleUi();
    return true;
  }

  function applyLanguage(lang) {
    localStorage.setItem(STORAGE_KEY, lang);
    document.documentElement.lang = lang;
    document.querySelectorAll('#siteLanguage').forEach(select => {
      select.value = lang;
    });

    setTranslateCookie(lang);

    if (!syncGoogleCombo(lang)) {
      location.reload();
      return;
    }

    setTimeout(hideInjectedGoogleUi, 50);
    setTimeout(hideInjectedGoogleUi, 300);
  }

  window.googleTranslateElementInit = function () {
    new google.translate.TranslateElement({
      pageLanguage: 'en',
      includedLanguages: 'en,hi',
      autoDisplay: false
    }, 'google_translate_element');

    const saved = localStorage.getItem(STORAGE_KEY) || 'en';
    setTimeout(() => {
      syncGoogleCombo(saved);
      hideInjectedGoogleUi();
    }, 250);
  };

  document.addEventListener('DOMContentLoaded', function () {
    installTranslatorHidingStyles();
    hideInjectedGoogleUi();

    if (!document.getElementById('google_translate_element')) {
      const holder = document.createElement('div');
      holder.id = 'google_translate_element';
      holder.setAttribute('aria-hidden', 'true');
      holder.style.cssText = 'display:none!important;width:0!important;height:0!important;overflow:hidden!important;';
      document.body.appendChild(holder);
    }

    const saved = localStorage.getItem(STORAGE_KEY) || 'en';
    document.documentElement.lang = saved;

    document.querySelectorAll('#siteLanguage').forEach(select => {
      select.value = saved;
      select.addEventListener('change', event => applyLanguage(event.target.value));
    });

    // Google injects its banner after page load/translation. Keep only our own selector visible.
    const observer = new MutationObserver(hideInjectedGoogleUi);
    observer.observe(document.documentElement, { childList: true, subtree: true });

    if (!document.querySelector('script[data-sufia-translator]')) {
      const script = document.createElement('script');
      script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
      script.async = true;
      script.dataset.sufiaTranslator = 'true';
      document.body.appendChild(script);
    }
  });
})();
