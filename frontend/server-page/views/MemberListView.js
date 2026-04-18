export class MemberListView {
  constructor({ listSelector }) {
    this.listElement = document.querySelector(listSelector);
  }

  render(members, { canModerate, currentUserId, onKick, onRoleChange, onProfileOpen }) {
    this.listElement.innerHTML = "";

    members.forEach((user) => {
      const li = document.createElement("li");
      li.className = "member-entry";

      const info = document.createElement("div");
      info.className = "member-info";

      let roleDisplay = user.role;
      let roleClass = "";

      if (user.role === "P1") {
        roleDisplay = "Fondateur";
        roleClass = "role-p1";
      } else if (user.role === "P2") {
        roleDisplay = "Administrateur";
        roleClass = "role-p2";
      } else if (user.role === "P3") {
        roleDisplay = "Modérateur";
        roleClass = "role-p3";
      }

      info.innerHTML = `<span>${user.username}</span> <span class="role-label ${roleClass}">${roleDisplay}</span>`;
      li.appendChild(info);

      if (canModerate && user.id !== currentUserId) {
        const actions = document.createElement("div");
        actions.className = "admin-actions";

        const kickButton = document.createElement("button");
        kickButton.textContent = "Kick";
        kickButton.onclick = (event) => {
          event.stopPropagation();
          onKick(user.id);
        };

        const roleButton = document.createElement("button");
        roleButton.textContent = "⇅ Rôle";
        roleButton.onclick = (event) => {
          event.stopPropagation();
          onRoleChange(user.id);
        };

        actions.appendChild(kickButton);
        actions.appendChild(roleButton);
        li.appendChild(actions);
      }

      li.onclick = () => onProfileOpen(user.id);
      this.listElement.appendChild(li);
    });
  }
}
