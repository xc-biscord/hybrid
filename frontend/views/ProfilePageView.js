const DEFAULT_AVATAR = "assets/default-user.png";

export class ProfilePageView {
  constructor({
    usernameSelector,
    avatarSelector,
    profileFormSelector,
    accountFormSelector,
    passwordFormSelector,
    bioSelector,
    avatarInputSelector,
    statusSelector,
    usernameInputSelector,
    emailInputSelector,
    newPasswordSelector,
    confirmPasswordSelector,
    currentPasswordSelector,
    toastWrapperSelector,
    toastTitleSelector,
    toastTextSelector
  }) {
    this.usernameElement = document.querySelector(usernameSelector);
    this.avatarElement = document.querySelector(avatarSelector);
    this.profileFormElement = document.querySelector(profileFormSelector);
    this.accountFormElement = document.querySelector(accountFormSelector);
    this.passwordFormElement = document.querySelector(passwordFormSelector);

    this.bioElement = document.querySelector(bioSelector);
    this.avatarInputElement = document.querySelector(avatarInputSelector);
    this.statusElement = document.querySelector(statusSelector);
    this.usernameInputElement = document.querySelector(usernameInputSelector);
    this.emailInputElement = document.querySelector(emailInputSelector);

    this.newPasswordElement = document.querySelector(newPasswordSelector);
    this.confirmPasswordElement = document.querySelector(confirmPasswordSelector);
    this.currentPasswordElement = document.querySelector(currentPasswordSelector);

    this.toastWrapperElement = document.querySelector(toastWrapperSelector);
    this.toastTitleElement = document.querySelector(toastTitleSelector);
    this.toastTextElement = document.querySelector(toastTextSelector);
  }

  showToast(title, message, duration = 3000) {
    this.toastTitleElement.textContent = title;
    this.toastTextElement.textContent = message;
    this.toastWrapperElement.classList.remove("hidden");

    setTimeout(() => {
      this.toastWrapperElement.classList.add("hidden");
    }, duration);
  }

  renderProfile(profile) {
    this.usernameElement.textContent = profile.username;
    this.setAvatar(profile.avatar_url);

    this.bioElement.value = profile.bio || "";
    this.avatarInputElement.value = profile.avatar_url || "";
    this.statusElement.value = profile.status || "";
    this.usernameInputElement.value = profile.username;
    this.emailInputElement.value = profile.email || "";
  }

  setAvatar(avatarUrl) {
    const resolvedAvatar = avatarUrl && avatarUrl.trim() ? avatarUrl : DEFAULT_AVATAR;

    this.avatarElement.src = resolvedAvatar;
    this.avatarElement.onerror = () => {
      this.avatarElement.onerror = null;
      this.avatarElement.src = DEFAULT_AVATAR;
    };
  }

  bindProfileSubmit(handler) {
    this.profileFormElement.addEventListener("submit", (event) => {
      event.preventDefault();

      handler({
        bio: this.bioElement.value,
        avatar_url: this.avatarInputElement.value,
        status: this.statusElement.value
      });
    });
  }

  bindAccountSubmit(handler) {
    this.accountFormElement.addEventListener("submit", (event) => {
      event.preventDefault();

      handler({
        username: this.usernameInputElement.value,
        email: this.emailInputElement.value
      });
    });
  }

  bindPasswordSubmit(handler) {
    this.passwordFormElement.addEventListener("submit", (event) => {
      event.preventDefault();

      handler({
        password: this.newPasswordElement.value,
        confirm: this.confirmPasswordElement.value,
        current_password: this.currentPasswordElement.value
      });
    });
  }
}
