<?php
if ($data['error'] !== null) {
	$output = [
		'header' => $data['name'],
		'body' => (new CTableInfo())
			->setNoDataMessage($data['error'])
			->toString()
	];
}
else {
	$style = $data['canvas']['top']
		? 'text-align: center; margin-top:'.$data['canvas']['top'].'px;'
		: 'text-align: center;';
	$item = (new CDiv())->addStyle($style);

	switch ($data['game_state']) {
		case CControllerWidgetTicTacToe::STATE_NO_GAME:
			$games_list = new CList();

			if ($data['games']) {
				$games_list->addItem([
					(new CSpan('Choose the opponent to play with or start a new game and ask others to join you.'))
						->addStyle('display:block; padding:0 15%;'),
					new CTag('br')
				]);
				foreach ($data['games'] as $game) {
					$games_list->addItem(
						(new CLink('Play with '.$game['opponent']))
							->setAttribute('data-itemid', $game['itemid'])
							->addClass('match-to-join')
					);
				}
			}
			else {
				$games_list->addItem([
					'There are no game matches to join.',
					new CTag('br'),
					new CTag('br'),
					'You can either to wait while someone appears or start a new game and ask others to join you.',
					new CTag('br')
				]);
			}

			$item->addItem(
					(new CDiv([
						$games_list,
						new CTag('br'),
						(new CButton('button', 'Start a new game'))->addClass('start-new-game-btn')
					]))->addStyle('display:table-cell; vertical-align:middle; max-width:70%;')
				)
				->addStyle('display:table; height:100%; width:100%; padding-bottom:33px;');
			break;

		case CControllerWidgetTicTacToe::STATE_NO_OPPONENT:
			$status_msg = 'Waiting for oponent.';

			if (count($data['games']) > 1) {
				$games_list = new CList();

				$games_list->addItem('There are other games as well...');
				foreach ($data['games'] as $game) {
					if (!$game['author']) {
						$games_list->addItem(
							(new CLink('Play with '.$game['opponent']))
								->setAttribute('data-itemid', $game['itemid'])
								->addClass('match-to-join')
						);
					}
				}
			}
			else {
				$games_list = null;
			}

			$item->addItem(
					(new CDiv([
						$status_msg,
						$games_list ? [new CTag('br'), new CTag('br'), $games_list] : null
					]))->addStyle('display:table-cell; vertical-align:middle;')
				)
				->addStyle('display:table; height:100%; width:100%; padding-bottom:33px;');
			break;

		case CControllerWidgetTicTacToe::STATE_PLAYER_TURN:
		case CControllerWidgetTicTacToe::STATE_OPPONENT_TURN:
		case CControllerWidgetTicTacToe::STATE_GAME_OVER:
			$item->addItem(
				(new CTag('canvas'))
					->setAttribute('width', $data['canvas']['size'])
					->setAttribute('height', $data['canvas']['size'])
					->addClass('game-board')
			);
			break;
	}

	$output = [
		'header' => $data['name'],
		'body' => $item->toString(),
		'script_inline' => $data['script_inline']
	];
}

if (($messages = getMessages()) !== null) {
	$output['messages'] = $messages->toString();
}

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);
