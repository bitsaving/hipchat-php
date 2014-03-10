#!/usr/bin/php
<?php

require 'vendor/autoload.php';

if (!isset($argv[1])) {
  echo "Usage: $argv[0] <token> [target]\n";
  die;
}

$token = $argv[1];
$version = isset($argv[2]) ? $argv[2] : 'v1';
$target = isset($argv[3]) ? $argv[3] : 'https://api.hipchat.com';
$hc = HipChat\HipChat::get_client($token, $target, $version);

echo "Testing HipChat API.\nTarget: $target\nToken: $token\n\n";

// get rooms
echo "Rooms:\n";
try {
  $rooms = $hc->get_rooms();
  foreach ($rooms as $room) {
    echo "Room $room->room_id\n";
    echo " - Name: $room->name\n";
    $room_data = $hc->get_room($room->room_id);
    echo " - Participants: ".count($room_data->participants)."\n";
    $last_message = reset($hc->get_rooms_history($room->room_id));
    echo " - last message: \"".$last_message->message."\" sent by ".$last_message->from->name." @ ".$last_message->date."\n";
  }
} catch (HipChat\HipChat_Exception $e) {
  echo "Oops! Error: ".$e->getMessage();
}

// get users
echo "\nUsers:\n";
try {
  $users = $hc->get_users();
  foreach ($users as $user) {
    echo "User $user->user_id\n";
    echo " - Name: $user->name\n";
    $user_data = $hc->get_user($user->user_id);
    echo " - Email: $user_data->email\n";
    echo " - Status: ".$user_data->status."\n";
  }
} catch (HipChat\HipChat_Exception $e) {
  echo "Oops! Error: ".$e->getMessage();
}
