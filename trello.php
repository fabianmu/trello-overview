<?php

/**
 * @file trello.php
 * Show the top two cards for each board.
 */

// Get auth info.
include 'trello.inc';

// Get the cards.
$boards = [];
foreach ($auth as $auth_key => $token) {
  $boards = array_merge($boards, _get_boards());
}

// Show the cards.
_html_start();
foreach ($boards as $board) {
  echo '<div class="board"' . $board->style . '><div class="board-name"><a href="' . $board->shortUrl . '" target="_blank">' . $board->name . '</a></div>';
  foreach ($board->lists as $list) {
    echo '<div class="list"><div class="list-name"><a href="' . $board->shortUrl . '" target="_blank">' . $list->name . '</a></div>';
    foreach ($list->cards as $card) {
      echo '<div class="card"><a href="' . $card->shortUrl . '" target="_blank">' . $card->name . '</a></div>';
    }
    echo '</div>';
  }
  echo '</div>';
}

_html_end();

// FUNCTIONS //
function _get_boards() {
  $boards = _trello_get('/members/me/boards');
  // $boards = array_slice($boards, 5);

  // Loop throught each board (logic loop).
  foreach ($boards as $board_key => $board) {

    // Archived board?  Don't show it.
    if ($board->closed) {
      unset($boards[$board_key]);
      continue;
    }

    // Grab lists for that board.
    $board->lists = _trello_get('/boards/' . $board->id . '/lists');

    // No lists?  Delete board.
    if (empty($board->lists)) {
      unset($boards[$board_key]);
      continue;
    }

    // Grey background?  Delete board.
    if ($board->prefs->background == 'grey') {
      unset($boards[$board_key]);
      continue;
    }

    // Find bgcolor or bgimage.
    if (is_array($board->prefs->backgroundImageScaled))
      $board->style = ' style="background: url(' . $board->prefs->backgroundImageScaled[3]->url . ');"';
    else if (isset($board->prefs->backgroundColor))
      $board->style = ' style="background-color: ' . $board->prefs->backgroundColor . ';"';
    else
      $board->style = '';

    // Loop through each list.
    foreach ($board->lists as $list_key => $list) {

      // Links?  Delete list.
      if ($list->name == 'Links') {
        unset($board->lists[$list_key]);
        continue;
      }

      // Grab cards for that list.
      $list->cards = _trello_get('/lists/' . $list->id . '/cards');

      // No cards?  Delete that list.
      if (empty($list->cards)) {
        unset($board->lists[$list_key]);
        continue;
      }

      // Only keep first two cards.
      $list->cards = array_slice($list->cards, 0, 2);
    }

  }
  return $boards;
}

function _trello_get($resource) {
  global $auth_key, $token;

  $handle = fopen('https://api.trello.com/1' . $resource . '?key=' . $auth_key . '&token=' . $token, 'rb');
  $data = json_decode(stream_get_contents($handle));
  fclose($handle);
  return $data;
}

function _html_start() {
  echo <<< EOF
<html>
<head>
<style>
body {
  font-family: helvetica, arial;
}
a {
  text-decoration: none;
  color: inherit;
}
div {
  /* border: 1px solid black; */
  margin: 4px 6px;
  padding: 4px 6px;
}
.board {
  display: inline-block;
  font-size: 16px;
  font-weight: bold;
  background-color: lightblue;
  background-repeat: no-repeat;
  background-size: cover !important;
}
.board-name {
  text-align: center;
  background-color: lightgrey;
}
.list {
  display: inline-block;
  font-size: 14px;
  vertical-align: top;
  color: #eee;
}
.list-name {
  text-align: center;
  background-color: rgba(0, 0, 0, 0.6);
}
.card {
  font-size: 12px;
  font-weight: normal;
  width: 128px;
  border: 1px solid lightgrey;
  color: #eee;
  background-color: rgba(0, 0, 0, 0.4);
}
</style>
</head>
<body>
EOF;
}

function _html_end() {
  global $boards;

  foreach ($boards as $board) {
    if ($board->name == 'NOPE Ziquid') {
      echo '<pre>';
      var_dump($board);
      echo '</pre>';
    }
  }

  echo <<< EOF
</body>
</html>
EOF;
}
