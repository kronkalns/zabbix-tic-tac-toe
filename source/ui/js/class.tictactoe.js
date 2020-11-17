/**
 * Widget hooks.
 */
function zbx_tictactoe_widget_trigger(hook_name) {
	var grid = Array.prototype.slice.call(arguments, -1),
		grid = grid.length ? grid[0] : null;

	if (grid) {
		if (hook_name === 'onResizeEnd') {
			jQuery('.dashbrd-grid-container').dashboardGrid('refreshWidget', grid.widget['uniqueid']);
//		} else if (hook_name === 'onWidgetRefresh') {
//			console.log(grid.widget);
//			return false;
		}
	}
}

/**
 * Main widget class.
 */
jQuery(function($) {
	"use strict";

	const YOU_WON = 1;
	const OPPONENT_WON = 2;
	const DRAWN = 3;

	const STATE_PLAYER_TURN = 1;
	const STATE_OPPONENT_TURN = 2;

	var ticTacToe = function() {
		this.canvasSize = 300;
		this.padding = 5;
		this.gridCellSize = (this.canvasSize - this.padding * 2) / 3;
		this.lineWidth = 6;
		this.figureLineWidth = 10;
		this.gridColor = '#d0cece';
		this.ghostColor = '#d5e9ee';
		this.crossLineColor = '#ff9800';
		this.figOColor = '#87cfc9';
		this.figXColor = '#94a261';
		this.frozen = true;
	};

	ticTacToe.prototype = {
		initGame: function(data) {
			this.widget = data.widget;
			this.canvas = data.widget['content_body'][0].getElementsByClassName('game-board')[0];
			this.context = this.canvas.getContext('2d');
			this.canvasSize = this.canvas.offsetWidth;

			this.drawGrid();
			this.game = data.figures || '.........';
			this.figure = data.figure;
			this.opponent = data.opponent;
			this.state = data.state;
			this.drawGameFigures();

			if (!this.showResult()) {
				if (data.state == 2) {
					this.showMessage('It\'s '+encode(this.opponent)+'\'s move.');
				} else {
					this.canvas.addEventListener('mouseup', this.onClickHandler.bind(this));
					this.canvas.addEventListener('mousemove', this.onMouseMoveHandler.bind(this));
					this.frozen = false;
				}
			}
		},
		showMessage: function() {
			let overlay = document.createElement('div');
			this.widget.content_body[0].appendChild(overlay);

			let mt = parseFloat(this.canvas.parentNode.offsetTop) + this.canvasSize / 2,
				style = 'margin:0px auto; text-align:center; font-size:1.2rem; line-height:1.8rem; margin-top:calc(' + mt + 'px - 20px);';

			overlay.style.position = 'absolute';
			overlay.style.height = 'calc(100% - 10px)';
			overlay.style.width = 'calc(100% - 10px)';
			overlay.style.backgroundColor = '#00000036';
			overlay.style.left = '5px';
			overlay.style.top = '5px';
			overlay.style.right = '5px';
			overlay.style.textAlign = 'center';
			overlay.innerHTML = '<div style="' + style + '">' + arguments[0] + '</div>';

			if (arguments.length > 1) {
				let x = 0;
				while (arguments[++x] != undefined) {
					var div = document.createElement('div');
					div.appendChild(arguments[x]);
					div.style.margin = '10px 0';
					overlay.appendChild(div);
				}
			}
		},
		getPoint: function(i) {
			if (i == 0) {
				return this.padding;
			} else if (i == 3) {
				return this.canvasSize - this.padding;
			} else {
				return (this.gridCellSize * i) + this.padding;
			}
		},
		drawGrid: function() {
			this.gridCellSize = (this.canvasSize - this.padding * 2) / 3;
			this.context.lineWidth = this.lineWidth;
			this.context.strokeStyle = this.gridColor;
			this.context.lineCap = 'round';
			this.context.beginPath();
			for (let i = 1; 3 > i; i++) {
				this.context.moveTo(this.getPoint(0), this.getPoint(i));
				this.context.lineTo(this.getPoint(3), this.getPoint(i));
			}
			for (let i = 1; 3 > i; i++) {
				this.context.moveTo(this.getPoint(i), this.getPoint(0));
				this.context.lineTo(this.getPoint(i), this.getPoint(3));
			}
			this.context.stroke();
		},
		drawO: function(pos, color) {
			let diameter = this.gridCellSize * 0.7;
			let centerX = this.gridCellSize / 2 + pos.x;
			let centerY = this.gridCellSize / 2 + pos.y;

			this.context.lineWidth = this.figureLineWidth;
			this.context.strokeStyle = color || this.figOColor;
			this.context.beginPath();
			this.context.arc(centerX, centerY, diameter / 2, 0 * Math.PI, 2 * Math.PI);
			this.context.stroke();
		},
		drawX: function(pos, color) {
			let a = this.gridCellSize * 0.6,
				p = (this.gridCellSize - a) / 2;

			this.context.lineWidth = this.figureLineWidth;
			this.context.strokeStyle = color || this.figXColor;
			this.context.beginPath();
			this.context.moveTo(pos.x + p, pos.y + p);
			this.context.lineTo(pos.x + p + a, pos.y + p + a);
			this.context.moveTo(pos.x + p + a, pos.y + p);
			this.context.lineTo(pos.x + p, pos.y + p + a);
			this.context.stroke();
		},
		drawGameFigures: function() {
			for (let i = 0; 9 > i; i++) {
				let char = this.game.charAt(i),
					x = this.getPoint(i % 3),
					y = this.getPoint(Math.ceil((i + 1) / 3) - 1);

				if (char === 'X') {
					this.drawX({x: x, y: y});
				} else if (char === 'O') {
					this.drawO({x: x, y: y});
				}
			}
		},
		getMousePosition: function(e) {
			const r = this.canvas.getBoundingClientRect();
			return {
				x: e.clientX - r.left,
				y: e.clientY - r.top
			};
		},
		getCell: function(e) {
			let pos = this.getMousePosition(e);

			let col = 0, x;
			do {x = this.getPoint(col); col++;} while (pos.x > x && 3 >= col);
			--col;

			let row = 0, y;
			do {y = this.getPoint(row); row++;} while (pos.y > y && 3 >= row);
			--row;

			if (row && col) {
				return {
					cell: --row * 3 + col,
					pos: {
						x: this.getPoint(--col),
						y: this.getPoint(row)
					}
				};
			} else {
				return null;
			}
		},
		cleanCanvas: function() {
			this.context.clearRect(0, 0, this.canvasSize, this.canvasSize);
		},
		getWinner: function() {
			let patterns = {'012':1, '345':1, '678':1, '036':2, '147':2, '258':2, '048':3, '246':4},
				pattern = null,
				figure;

			for (let p in patterns) {
				let fig = this.game.charAt(p[0]),
					match = false;

				if (fig !== '.') {
					for (let i = 1; 3 > i; i++) {
						match = (this.game.charAt(+p[i]) === fig);
						if (!match) {
							break;
						}
					}
				}

				if (match) {
					pattern = p;
					figure = fig;
					break;
				}
			}

			if (pattern) {
				return {
					pattern: pattern,
					result: figure === this.figure ? YOU_WON : OPPONENT_WON,
					type: patterns[pattern]
				};
			} else if (this.game.indexOf('.') == -1) {
				return {
					result: DRAWN
				};
			} else {
				return null;
			}
		},
		showResult: function() {
			let result = this.getWinner();

			if (result !== null) {
				let x1, x2, y1, y2, p = this.gridCellSize * 0.1;

				var btn = document.createElement('button');
				btn.innerHTML = 'Play again with '+encode(this.opponent);
				btn.classList.add('start-new-game-btn');
				btn.addEventListener('click', function() {
					startNewMatch(this.widget)
						.then(() => {
							jQuery('.dashbrd-grid-container').dashboardGrid('refreshWidget', widget['uniqueid']);
						})
						.catch(error => {
							console.log(error)
						});
				}.bind(this));

				var btn2 = document.createElement('a');
				btn2.innerHTML = 'Start another game';
				btn2.classList.add('start-new-game-btn');
				btn2.classList.add('link-action');
				btn2.addEventListener('click', function() {
					startNewGame(this.widget)
						.then(() => {
							//refresh(widget);
							jQuery('.dashbrd-grid-container').dashboardGrid('refreshWidget', widget['uniqueid']);// this.widget?
						})
						.catch(error => {
							console.log(error)
						});
				}.bind(this));

				if (result.result == DRAWN) {
					this.showMessage('You both are great!', btn, btn2);
					return true;
				} else {
					this.showMessage(result.result == YOU_WON ? 'You won!' : encode(this.opponent)+' won!', btn, btn2);
				}

				let c1 = +result.pattern.charAt(0);
				switch (result.type) {
					case 1:
						x1 = this.getPoint(0) + p;
						x2 = this.getPoint(3) - p;
						y1 = this.getPoint(c1 / 3) + (this.gridCellSize * 0.5);
						y2 = y1;
						break;
					case 2:
						x1 = this.getPoint(c1 + 0.5);
						x2 = x1;
						y1 = this.getPoint(0) + p;
						y2 = this.getPoint(3) - p;
						break;
					case 3:
						x1 = this.getPoint(0) + p;
						x2 = this.getPoint(3) - p;
						y1 = this.getPoint(0) + p;
						y2 = this.getPoint(3) - p;
						break;
					case 4:
						x1 = this.getPoint(3) - p;
						x2 = this.getPoint(0) + p;
						y1 = this.getPoint(0) + p;
						y2 = this.getPoint(3) - p;
						break;
				}

				this.context.lineWidth = this.figureLineWidth - 4;
				this.context.strokeStyle = this.crossLineColor;
				this.context.lineCap = 'round';
				this.context.beginPath();
				this.context.moveTo(x1, y1);
				this.context.lineTo(x2, y2);
				this.context.stroke();

				return true;
			}

			return false;
		},
		onMouseMoveHandler: function(e) {
			if (this.frozen || (this.figure !== 'X' && this.figure !== 'O')) {
				return;
			}

			let cell = this.getCell(e),
				result = this.getWinner();

			if (cell !== null && result === null && this.game.charAt(cell.cell-1) === '.') {
				this.cleanCanvas();
				this.drawGrid();
				this.drawGameFigures();
				if (this.figure === 'X') {
					this.drawX(cell.pos, this.ghostColor);
				} else {
					this.drawO(cell.pos, this.ghostColor);
				}
			} else if (cell !== null && result === null) {
				this.cleanCanvas();
				this.drawGrid();
				this.drawGameFigures();
			}
		},
		onClickHandler: function(e) {
			if (this.frozen || (this.figure !== 'X' && this.figure !== 'O')) {
				return;
			}

			this.frozen = true;
			let cell = this.getCell(e);
			if (this.game.charAt(cell.cell - 1) === '.') {
				this.updateLocally(cell.cell-1);

				makeTheMove(cell.cell, this.widget).then(() => {
					//setTimeout(function() {
					//	jQuery('.dashbrd-grid-container').dashboardGrid('refreshWidget', this.widget['uniqueid']);
					//}, 2000);
					this.frozen = true;
				})
				.catch(error => {
					console.log(error)
				});
			} else {
				this.frozen = false;
			}
		},
		updateLocally: function(index) {
			this.game = this.game.substr(0, index) + this.figure + this.game.substr(index + 1);
			let x = this.getPoint(index % 3),
				y = this.getPoint(Math.ceil((index + 1) / 3) - 1);

			if (this.figure === 'X') {
				this.drawX({x: x, y: y});
			} else if (this.figure === 'O') {
				this.drawO({x: x, y: y});
			}

			!this.showResult() && this.showMessage('It\'s '+encode(this.opponent)+'\'s move.');
		}
	};

	var makeTheMove = function(cell, widget) {
		return new Promise((resolve, reject) => {
			var url = new Curl('zabbix.php');
			url.setArgument('action', 'widget.tictactoe.update');

			$.ajax({
				url: url.getUrl(),
				type: 'POST',
				data: {
					'fields': JSON.stringify(widget.fields),
					'uniqueid': widget.uniqueid,
					'move': cell,
					'do': 'makeTheMove'
				},
				success: function(data) {
					resolve(data);
				},
				error: function(error) {
					reject(error);
				}
			});
		});
	};

	var startNewGame = function(widget) {
		return new Promise((resolve, reject) => {
			var url = new Curl('zabbix.php');
			url.setArgument('action', 'widget.tictactoe.update');

			$.ajax({
				url: url.getUrl(),
				type: 'POST',
				data: {
					'fields': JSON.stringify(widget.fields),
					'uniqueid': widget.uniqueid,
					'do': 'startNewGame'
				},
				success: function(data) {
					resolve(data);
				},
				error: function(error) {
					reject(error);
				}
			});
		});
	};

	var startNewMatch = function(widget) {
		return new Promise((resolve, reject) => {
			var url = new Curl('zabbix.php');
			url.setArgument('action', 'widget.tictactoe.update');

			$.ajax({
				url: url.getUrl(),
				type: 'POST',
				data: {
					'fields': JSON.stringify(widget.fields),
					'uniqueid': widget.uniqueid,
					'do': 'startNewMatch'
				},
				success: function(data) {
					resolve(data);
				},
				error: function(error) {
					reject(error);
				}
			});
		});
	};

	var joinTheGame = function(widget, itemid) {
		return new Promise((resolve, reject) => {
			var url = new Curl('zabbix.php');
			url.setArgument('action', 'widget.tictactoe.update');

			$.ajax({
				url: url.getUrl(),
				type: 'POST',
				data: {
					'fields': JSON.stringify(widget.fields),
					'uniqueid': widget.uniqueid,
					'itemid': itemid,
					'do': 'joinTheGame'
				},
				success: function(data) {
					resolve(data);
				},
				error: function(error) {
					reject(error);
				}
			});
		});
	};

	var refresh = function(widget, ttt) {
//		console.log('refresh', (ttt.opponent === 'Bob' ? 'Alice' : 'Bob'), ttt.state);
//
//		if (ttt.state == STATE_OPPONENT_TURN) {
//			jQuery('.dashbrd-grid-container').dashboardGrid('unpauseWidgetRefresh', widget['uniqueid']);
//			jQuery('.dashbrd-grid-container').dashboardGrid('refreshWidget', widget['uniqueid']);
//			jQuery('.dashbrd-grid-container').dashboardGrid('pauseWidgetRefresh', widget['uniqueid']);
//		} else {
//			jQuery('.dashbrd-grid-container').dashboardGrid('unpauseWidgetRefresh', widget['uniqueid']);
//		}
	};

	var encode = function(str) {
		return str.replace(/[\u00A0-\u9999<>\&]/gim, (i) => '&#'+i.charCodeAt(0)+';').replace(/&/gim, '&amp;');
	};

	var methods = {
		init: function(options, widget) {
			if (options.state > 0) {
				var ttt = new ticTacToe();
				ttt.initGame(jQuery.extend(options, {widget: widget}));

				setTimeout(() => {
					clearTimeout(ttt.refreshTimeout);
					ttt.refreshTimeout = setTimeout(() => refresh(widget, ttt), 3000);
				});
			} else if (options.state == 0) {
				// waiting oponent
			} else {
				let games_btns = widget['content_body'][0].getElementsByClassName('match-to-join'),
					start_btns = widget['content_body'][0].getElementsByClassName('start-new-game-btn');

				for (let btn of games_btns) {
					btn.addEventListener('click', function(event) {
						joinTheGame(widget, event.target.dataset.itemid)
							.then(() => {
								jQuery('.dashbrd-grid-container').dashboardGrid('refreshWidget', widget['uniqueid']);
							})
							.catch(error => {
								console.log(error)
							});
					}.bind(this));
				}

				start_btns.length && start_btns[0].addEventListener('click', function() {
					startNewGame(widget)
						.then(() => {
							jQuery('.dashbrd-grid-container').dashboardGrid('refreshWidget', widget['uniqueid']);
						})
						.catch(error => {
							console.log(error)
						});
				}.bind(this));
			}
		}
	};

	$.fn.tictactoe = function() {
		return methods.init.apply(this, arguments);
	};
});
