<?php

/**
 * Tic-tac-toe widget view controller.
 *
 * GAME STATES:
 * -1 - no game;
 *  0 - no opponent;
 *  1 - player's turn;
 *  2 - opponent's turn;
 *  3 - game over.
 *
 * PLAYERS' ROLES:
 * Player - 'X';
 * Opponent - 'O'.
 *
 * Opponent has first move.
 */
class CControllerWidgetTicTacToe extends CControllerWidget {

	protected $gameItemKey = 'tictactoe';
	protected $player;
	protected $game;
	protected $host;
	protected $state;

	const ZBX_TIMEOUT = 3;

	const STATE_NO_GAME = -1;
	const STATE_NO_OPPONENT = 0;
	const STATE_PLAYER_TURN = 1;
	const STATE_OPPONENT_TURN = 2;
	const STATE_GAME_OVER = 3;

	const FIGURE_PLAYER = 'X';
	const FIGURE_OPPONENT = 'O';
	const FIGURE_EMPTY_SLOT = '.';

	const BOARD_WIDTH_MIN = 100;
	const BOARD_WIDTH_MAX = 1600;
	const BOARD_HEIGHT_MIN = 100;
	const BOARD_HEIGHT_MAX = 1600;

	protected $zbx_server;
	protected $zbx_port;

	protected $player_figure;
	protected $opponent_figure;
	protected $game_figures;
	protected $empty_board;

	public function init() {
		global $ZBX_SERVER, $ZBX_SERVER_PORT;

		$this->zbx_server = $ZBX_SERVER;
		$this->zbx_port = $ZBX_SERVER_PORT;

		$this->state = self::STATE_NO_GAME;
	}

	public function __construct() {
		parent::__construct();

		$this->setType(WIDGET_TIC_TAC_TOE);
		$this->empty_board = str_repeat(self::FIGURE_EMPTY_SLOT, 9);
	}

	protected function doAction() {
		return;
	}

	/**
	 * Join the selected game as second player.
	 */
	protected function joinTheGame(int $itemid, ?string &$error): bool {
		$items = API::Item()->get([
			'output' => ['itemid', 'key_', 'description'],
			'hostids' => $this->host['hostid'],
			'itemids' => $itemid,
			'search' => ['key_' => $this->gameItemKey],
			'monitored' => true
		]);
		$msg = '';

		if (($item = reset($items)) !== false) {
			$details = explode(',', substr($item['key_'], strlen($this->gameItemKey) + 1, -1));
			if ($details[1] === '') {
				$details[1] = $this->player['id'];
			}
			else {
				$msg = 'Ouch! Someone else already picked this game.';
			}

			$description = json_decode($item['description'], true);
			$oponents_nickname = ($description && array_key_exists($details[0], $description))
				? $description[$details[0]]
				: $details[0];

			$result = API::Item()->update([
				'key_' => $this->gameItemKey . '[' . implode(',', $details) . ']',
				'description' => json_encode([
					$details[0] => $oponents_nickname,
					$this->player['id'] => $this->player['nickname']
				]),
				'itemid' => $itemid
			]);

			if (!$result) {
				$msg = 'Unable to join the game.';
			}
		}
		else {
			$msg = 'Sorry, this game is not available anymore.';
		}

		if ($msg !== '') {
			$error = $msg . ' Let\'s choose another game or start your own.';
			return false;
		}

		return true;
	}

	/**
	 * Function to find the host used for data exchange.
	 */
	protected function initStorageHost(array $fields, ?string &$error): bool {
		$hosts = array_key_exists('hostid', $fields)
			? API::Host()->get([
				'output' => ['hostid', 'host'],
				'hostids' => $fields['hostid'],
				'editable' => true
			])
			: [];

		if (($host = reset($hosts)) !== false) {
			$this->host = $host;
			return true;
		}
		else {
			$error = _('No permissions to referred object or it does not exist!');
			return false;
		}
	}

	/**
	 * Set player details.
	 */
	protected function initPlayer(array $fields, ?string &$error): bool {
		if (array_key_exists('reference', $fields) && $fields['reference'] === '') {
			$error = 'Widget reference missing.';
			return false;
		}

		$playerid = $fields['reference'].'_'.CWebUser::$data['userid'];
		$this->player = [
			'id' => $playerid,
			'nickname' => (array_key_exists('nickname', $fields) && $fields['nickname'] !== '')
				? $fields['nickname']
				: $playerid
		];

		return true;
	}

	protected function loadActiveGames(): array {
		$items = API::Item()->get([
			'output' => ['itemid', 'key_', 'value_type', 'description'],
			'hostids' => $this->host['hostid'],
			'search' => ['key_' => $this->gameItemKey],
			'monitored' => true
		]);

		// Extract details.
		foreach ($items as $i => &$item) {
			$details = explode(',', substr($item['key_'], strlen($this->gameItemKey) + 1, -1));
			$nicknames = json_decode($item['description'], true) ? : [];

			if (count($details) == 2) {
				unset($item['description']);

				if ($this->player['id'] === $details[0]) {
					$item += [
						'player' => $this->player['nickname'],
						'opponent' => array_key_exists($details[1], $nicknames)
							? $nicknames[$details[1]]
							: $details[1],
						'author' => true
					];
					if (!$this->game) {
						$this->game = $item;
					}
					$this->player_figure = self::FIGURE_PLAYER;
					$this->opponent_figure = self::FIGURE_OPPONENT;
				}
				elseif ($this->player['id'] === $details[1]) {
					$item += [
						'player' => $this->player['nickname'],
						'opponent' => array_key_exists($details[0], $nicknames)
							? $nicknames[$details[0]]
							: $details[0],
						'author' => false
					];
					if (!$this->game) {
						$this->game = $item;
					}
					$this->player_figure = self::FIGURE_OPPONENT;
					$this->opponent_figure = self::FIGURE_PLAYER;
				}
				elseif ($details[1] === '') {
					$item += [
						'player' => '',
						'opponent' => array_key_exists($details[0], $nicknames)
							? $nicknames[$details[0]]
							: $details[0],
						'author' => false
					];
				}
				else {
					unset($items[$i]);
					continue;
				}
			}
			else {
				unset($items[$i]);
			}
		}
		unset($item);

		return $items;
	}

	/**
	 * Function to check current game validity and return current game result.
	 */
	protected function loadGameFigures(?string &$error): string {
		$figures = Manager::History()->getLastValues([$this->game], 10);
		if (!$figures) {
			return $this->empty_board;
		}

		$figures = reset($figures);
		$figures = array_slice($figures, 0, (array_search($this->empty_board, array_column($figures, 'value'))));

		if (!$this->checkIntegrity($figures)) {
			$error = 'Results are corrupted.';
			$this->state = self::STATE_GAME_OVER;
		}

		return $figures ? reset($figures)['value'] : $this->empty_board;
	}

	/**
	 * Check integrity of all previous moves of the game.
	 */
	protected function checkIntegrity(array $figures): bool {
		$moves = array_fill_keys(range(0, 8), self::FIGURE_EMPTY_SLOT);
		foreach (array_reverse($figures) as $index => $figure) {
			$expected_move = ($index % 2 == 0) ? self::FIGURE_OPPONENT : self::FIGURE_PLAYER;
			$diff = array_diff_assoc(str_split($figure['value']), $moves);
			if (count($diff) != 1 || reset($diff) !== $expected_move) {
				return false;
			}
			$moves[array_keys($diff)[0]] = $expected_move;
		}

		return true;
	}

	/**
	 * Get the current state of the figures and who's turn it is.
	 */
	protected function loadMatchResult(&$error) {
		$this->game_figures = $this->loadGameFigures($error);
		$empty_slots = substr_count($this->game_figures, self::FIGURE_EMPTY_SLOT);

		// Get current state.
		if ($empty_slots == 0) {
			$this->state = self::STATE_GAME_OVER;;
		}
		elseif ($empty_slots == 9) {
			$this->state = $this->game['author'] ? self::STATE_OPPONENT_TURN : self::STATE_PLAYER_TURN;
		}
		elseif (((9 - $empty_slots) % 2) == 0) {
			// Same number of moves.
			$this->state = $this->game['author'] ? self::STATE_OPPONENT_TURN : self::STATE_PLAYER_TURN;
		}
		else {
			// Opponent has done one move more.
			$this->state = $this->game['author'] ? self::STATE_PLAYER_TURN : self::STATE_OPPONENT_TURN;
		}
	}

	/**
	 * Send new game state using Zabbix sender protocol.
	 *
	 * @param string           $value
	 * @param string|nullable  $error
	 *
	 * @return boolean
	 */
	protected function sendTrapperValue(string $value, &$error): bool {
        $settings = [
			'usec' => (self::ZBX_TIMEOUT - floor(self::ZBX_TIMEOUT)) * 1e6,
			'sec' => floor(self::ZBX_TIMEOUT)
		];

		$data = json_encode([
            'request' => 'sender data',
            'data' => [[
				'host' => $this->host['host'],
				'key' => $this->game['key_'],
				'value' => $value,
				'clock' => time()
			]]
        ]);

		$msg = "ZBXD\1" . pack('V', strlen($data)) . "\0\0\0\0" . $data;

		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
			$error = socket_strerror(socket_last_error());
			return false;
        }

		socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $settings);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $settings);

		$result = socket_connect($socket, $this->zbx_server, $this->zbx_port);

        if (($result = socket_send($socket, $msg, strlen($msg), 0)) === false) {
			//$error = error(socket_strerror(socket_last_error($socket)));
			$error = 'Server is not reachable.';
			return false;
        }
        socket_recv($socket, $resp, 1024, 0);

        if ($resp) {
            $ret = json_decode(mb_substr($resp, 13), true);
			if ($ret['response'] === 'success') {
				$ret = !(strstr($ret['info'], 'failed: 1;'));
				if (!$ret) {
					$error = 'This can happen if Zabbix configuration cache is not reloaded.';
				}
			}
        }
		else {
			$error = 'Server is not reachable.';
			$ret = false;
		}

        socket_close($socket);

		return $ret;
	}

	/**
	 * Start a new game (create an item to store player moves and other game details).
	 */
	protected function createNewGame(): void {
		API::Item()->create([
			'name' => 'Tic-tac-toe match',
			'key_' => $this->gameItemKey . '[' . $this->player['id'] . ',]',
			'hostid' => $this->host['hostid'],
			'type' => ITEM_TYPE_TRAPPER,
			'value_type' => ITEM_VALUE_TYPE_STR,
			'description' => json_encode([
				$this->player['id'] => $this->player['nickname']
			])
		]);
	}
}
