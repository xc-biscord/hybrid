export class ServerStore {
  constructor(serverId) {
    this.serverId = serverId;
    this.currentChannelId = null;
    this.myRole = null;
    this.isP1 = false;
    this.currentUserId = null;
    this.ctrlBActif = false;
  }

  setCurrentProfile(profile) {
    this.currentUserId = profile.id;
    this.isP1 = profile.is_p1 === true;
  }

  setMyRole(role) {
    this.myRole = role;
  }

  setCurrentChannel(channelId) {
    this.currentChannelId = channelId;
  }

  setCtrlBActif(value) {
    this.ctrlBActif = value;
  }

  canModerateMembers() {
    return this.isP1 || this.myRole === "P2";
  }

  canDeleteMessage(authorId) {
    return this.isP1 || this.myRole === "P2" || this.myRole === "P3" || authorId === this.currentUserId;
  }
}
