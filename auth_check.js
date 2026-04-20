fetch("/api/check_auth.php", { credentials: "include" })
  .then(res => res.json())
  .then(data => {
    if (!data.logged_in) {
      window.location.href = "/index.html";
    }
  });
