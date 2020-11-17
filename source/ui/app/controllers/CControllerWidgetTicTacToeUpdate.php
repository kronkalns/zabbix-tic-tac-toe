<?php

class CControllerWidgetTicTacToeUpdate extends CControllerWidgetTicTacToe {
	public function __construct() {
		parent::__construct();

		$this->setValidationRules([
			'do' => 'required|in startNewGame,startNewMatch,makeTheMove,joinTheGame',
			'uniqueid' => 'required|string',
			'move' => 'in 1,2,3,4,5,6,7,8,9',
			'itemid' => 'db items.itemid',
			'fields' => 'json'
		]);
	}

	protected function doAction() {
		$fields = $this->getForm()->getFieldsData();
		$error = null;

		if ($this->initPlayer($fields, $error) && $this->initStorageHost($fields, $error)) {
			switch ($this->getInput('do')) {
				case 'joinTheGame':
					$itemid = $this->getInput('itemid', 0);
					if ($itemid != 0) {
						$this->joinTheGame($itemid, $error);
						$this->sendTrapperValue($this->empty_board, $error);
					}
					break;

				case 'startNewMatch':
					$this->loadActiveGames();
					$this->loadMatchResult($error);
					$this->sendTrapperValue($this->empty_board, $error);
					break;

				case 'startNewGame':
					$this->loadActiveGames();
					if ($this->game) {
						API::Item()->delete([$this->game['itemid']]);
					}
					$this->createNewGame();
					break;

				case 'makeTheMove':
					$move = $this->getInput('move', 0);
					if ($move != 0) {
						$this->loadActiveGames();
						$this->loadMatchResult($error);

						$str_before = substr($this->game_figures, 0, $move - 1);
						$str_after = substr($this->game_figures, $move, 9);
						$result = $str_before . $this->player_figure . $str_after;

						$this->sendTrapperValue($result, $error);
					}
					break;
			}
		}

		$this->setResponse(new CControllerResponseData([
			'game' => [
				'error' => $error
			],
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}
