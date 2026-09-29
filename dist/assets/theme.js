// Pasang tema sebelum halaman digambar agar tidak berkedip: pilihan tersimpan (kunci sama dengan app.js), atau ikut pengaturan sistem.
(()=>{let theme=null;try{theme=localStorage.getItem('sikap_theme');}catch{}if(theme!=='light'&&theme!=='dark')theme=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=theme;})();
