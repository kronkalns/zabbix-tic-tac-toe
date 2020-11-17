<?php

class CControllerWidgetTicTacToeView extends CControllerWidgetTicTacToe {

	public function __construct() {
		parent::__construct();

		$this->setValidationRules([
			'name' => 'string',
			'uniqueid' => 'required|string',
			'initial_load' => 'in 0,1',
			'content_width' => 'int32|ge '.self::BOARD_WIDTH_MIN.'|le '.self::BOARD_WIDTH_MAX,
			'content_height' => 'int32|ge '.self::BOARD_HEIGHT_MIN.'|le '.self::BOARD_HEIGHT_MAX,
			'fields' => 'json'
		]);
	}

	protected function doAction() {
		$fields = $this->getForm()->getFieldsData();
		$error = null;
		$items = [];

		if ($this->initPlayer($fields, $error) && $this->initStorageHost($fields, $error)) {
			$items = $this->loadActiveGames();

			if (!$this->game) {
				CArrayHelper::sort($items, ['clock' => ZBX_SORT_DOWN]);
			}
			else {
				if ($this->game['opponent'] === '') {
					$this->state = self::STATE_NO_OPPONENT;
				}
				else {
					$this->loadMatchResult($error);

					// Check if server is ready.
					if ($this->game_figures === $this->empty_board) {
						$this->sendTrapperValue($this->empty_board, $error);
					}
				}
			}
		}

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->getDefaultHeader()),
			'script_inline' => $this->getScript(),
			'error' => $error,
			'game_state' => $this->state,
			'game' => $this->game,
			'games' => $items,
			'canvas' => [
				'size' => min($this->getInput('content_width'), $this->getInput('content_height')) - 4,
				'top' => ($this->getInput('content_height') > $this->getInput('content_width'))
					? ((int) $this->getInput('content_height') - (int) $this->getInput('content_width')) / 2
					: 0
			],
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}

	protected function getScript(): string {
		$uniqueid = $this->getInput('uniqueid');
		$game_options = [
			'state' => $this->state,
			'figures' => $this->game_figures,
			'figure' => $this->player_figure,
			'opponent' => $this->game['opponent']
		];

		$script_inline =
			'var widget = jQuery(".dashbrd-grid-container").dashboardGrid(\'getWidgetsBy\', \'uniqueid\', "'.$uniqueid.'");'.
			'jQuery(widget[0]["content_body"]).tictactoe('. json_encode($game_options).', widget[0]);';

		if ($this->getInput('initial_load', 1)) {
			$script_inline .=
				'jQuery(".dashbrd-grid-container").dashboardGrid("addAction", "onResizeEnd",'.
					'"zbx_tictactoe_widget_trigger", "'.$uniqueid.'", {'.
						'parameters: ["onResizeEnd"],'.
						'grid: {widget: 1},'.
						'trigger_name: "tictactoe_widget_resize_end_'.$uniqueid.'"'.
			'});';

//			$script_inline .=
//				'jQuery(".dashbrd-grid-container").dashboardGrid("addAction", "timer_refresh",'.
//					'"zbx_tictactoe_widget_trigger", "'.$uniqueid.'", {'.
//						'parameters: ["onWidgetRefresh"],'.
//						'grid: {widget: 1},'.
//						'trigger_name: "tictactoe_widget_refresh_'.$uniqueid.'"'.
//					'}'.
//				');';
		}

		return $script_inline;
	}
}
