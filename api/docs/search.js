window.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('search-input');
    const list = document.getElementById('sidebar-list');
    const items = list.getElementsByTagName('li');
  
    input.addEventListener('input', () => {
      const filter = input.value.toLowerCase();
  
      for (let item of items) {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(filter) ? '' : 'none';
      }
    });
  });
  