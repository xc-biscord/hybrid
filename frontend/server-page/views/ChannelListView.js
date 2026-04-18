export class ChannelListView {
  constructor({ listSelector, currentChannelNameSelector }) {
    this.listElement = document.querySelector(listSelector);
    this.currentChannelNameElement = document.querySelector(currentChannelNameSelector);
  }

  render(channels, onChannelClick) {
    this.listElement.innerHTML = "";

    channels.forEach((channel) => {
      const li = document.createElement("li");
      li.textContent = `#${channel.name}`;
      li.onclick = () => {
        this.currentChannelNameElement.textContent = `#${channel.name}`;
        onChannelClick(channel);
      };
      this.listElement.appendChild(li);
    });
  }
}
