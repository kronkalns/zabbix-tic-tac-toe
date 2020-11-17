# Zabbix Tic-Tac-Toe
Zabbix Tic-Tac-Toe is a multiplayer game widget for Zabbix dashboard. It's designed for Zabbix 5.2.

### How it works?
Zabbix [Tic-Tac-Toe](https://en.wikipedia.org/wiki/Tic-tac-toe) widget is using Zabbix trapper item to exchange the moves between players. To share the user moves, widget simulates Zabbix Sender. Before starting a game, both parties have to share the same editable host in widget configuration dialog.

While configuring user roles, you should consider following minimum required permissions:

 - Access to UI elements: Dashboard
 - Allowed API methods: `host.get`, `item.*`, `history.get`

Please be aware that it may take up to 2 minutes for Zabbix server to reload the configuration cache. It means that once both players have joined the game, it may take a while before Zabbix server is ready to accept the game moves sent to the new Zabbix trapper item.

### What can I expect?
As mentioned before, first you have to specify the same host, editable by all potential players. You also have to set your nickname to help others find out who is who.

![Widget configuration](images/edit-widget.png?raw=true)

Once widget is properly configured, Tic-Tac-Toe widget will ask someone to start a game.

![Start a new game screen](images/start-a-new-game.png?raw=true)

Users sharing the same editable host will see the game you just created.

![Join the game screen](images/join-the-game.png?raw=true)

The game board allows players to take their turns. The person who joined the last makes first move.

![Game board](images/game-board.png?raw=true)

The person who marks three figures in the row is the winner.

![Game completed](images/game-completed.png?raw=true)

### Installation
Because Zabbix doesn't support third party widgets, you will need to get your hands dirty to make it working. Installation can be done in following ways:

 - By copying files under directory [source/ui](source/ui) onto your Zabbix frontend codebase and applying patch [source/register-widget.patch](source/register-widget.patch);
 - By applying complete patch file [complete-source-diff.patch](complete-source-diff.patch) which contains all necessary changes in a single file.

**Do it at your own risk.**

### Todo
 - Make custom refresh mechanism for more smooth refresh.
 - Adopt colors for all themes.
 - Improve UI/UX.
