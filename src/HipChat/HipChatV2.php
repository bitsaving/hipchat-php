<?php

namespace HipChat;

class HipChatV2 extends BasicHipChatClient {

  /////////////////////////////////////////////////////////////////////////////
  // Room functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get information about a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/show
   */
  public function get_room($room_id) {
    $room_id = urlencode($room_id);
    $response = $this->make_request("room/{$room_id}");
    return $this->item_to_room($response);
  }

  /**
   * Get list of rooms
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/list
   */
  public function get_rooms() {
    $response = $this->make_request("room");
    return $this->items_to_rooms($response);
  }

  /**
   * Send a message to a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/message
   */
  public function message_room($room_id, $from, $message, $notify = false,
                               $color = self::COLOR_YELLOW,
                               $message_format = self::FORMAT_HTML) {
    $room_id = urlencode($room_id);
    $response = $this->make_request("room/$room_id/notification", array(
        'color' => $color,
        'message' => $message,
        'notify' => $notify,
        'format' => $message_format),
      'POST');

    $result = new \stdClass();
    $result->status = "sent";
    return $result;
  }

  /**
   * Get chat history for a room
   *
   * @see https://www.hipchat.com/docs/api/method/rooms/history
   */
  public function get_rooms_history($room_id, $date = 'recent') {
    $room_id = urlencode($room_id);
    $response = $this->make_request("room/{$room_id}/history", array('date' => $date));
    return $this->items_to_history($response);
  }

  /**
   * Set a room's topic
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/topic
   */
  public function set_room_topic($room_id, $topic, $from = null) {
    $this->make_request("room/{$room_id}/topic", array('topic' => $topic), 'POST');
    $response = new \stdClass();
    $response->status = "OK";
    return $response;
  }

  /**
   * Create a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/create
   */
  public function create_room($name, $owner_user_id = null, $privacy = null, $topic = null, $guest_access = null) {
    $response = $this->make_request("room", array(
        'guest_access' => $guest_access,
        'name' => $name,
        'owner_user_id' => $owner_user_id,
        'privacy' => $privacy),
      'POST');
    if (!empty($topic)) {
      $topic_response = $this->make_request("room/{$response->id}/topic", array('topic' => $topic), 'PUT');
    }
    $result = $this->get_room($response->id);
    return $result;
  }

  /**
   * Delete a room
   *
   * @see http://api.hipchat.com/docs/api/method/rooms/delete
   */
  public function delete_room($room_id) {
    $room_id = urlencode($room_id);
    $response = $this->make_request("room/$room_id", array(), 'DELETE');
    $result = new \stdClass();
    $result->deleted = true;
    return $result;
  }

  /////////////////////////////////////////////////////////////////////////////
  // User functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Get information about a user
   *
   * @see http://api.hipchat.com/docs/api/method/users/show
   */
  public function get_user($user_id) {
    $user_id = urlencode($user_id);
    $response = $this->make_request("user/{$user_id}");
    return $this->item_to_user($response);
  }

  /**
   * Get list of users
   *
   * @see http://api.hipchat.com/docs/api/method/users/list
   */
  public function get_users() {
    $response = $this->make_request('user');
    return $this->items_to_users($response);
  }

  /////////////////////////////////////////////////////////////////////////////
  // Helper functions
  /////////////////////////////////////////////////////////////////////////////

  protected function items_to_rooms($response) {
    $rooms = array();
    foreach ($response->items as $item) {
      $rooms[] = $this->item_to_room($item);
    }
    return $rooms;
  }

  protected function item_to_room($item) {
    $r = new \stdClass();
    $r->room_id = $item->id;
    foreach (array('name', 'topic', 'last_active', 'created', 'is_archived', 'privacy', 'guest_access_url', 'xmpp_jid') as $field) {
      $r->$field = isset($item->$field) ? $item->$field : null;
    }
    $r->owner_user_id = isset($item->owner->id) ? $item->owner->id : null;
    $r->is_private = isset($item->is_guest_accessible) ? !$item->is_guest_accessible : null;
    if (isset($item->participants) && is_array($item->participants)) {
      $participants = array();
      foreach ($item->participants as $participant) {
        $p = new \stdClass();
        $p->user_id = $participant->id;
        $p->name = $participant->name;
        $participants[] = $p;
      }
      $r->participants = $participants;
    } else {
      $r->participants = null;
    }
    return $r;
  }

  protected function items_to_users($response) {
    $users = array();
    foreach ($response->items as $item) {
      $user = $this->item_to_user($item);
      $users[] = $user;
    }
    return $users;
  }

  protected function item_to_user($item) {
    $user = new \stdClass();
    foreach (array('name', 'mention_name', 'email', 'title', 'photo_url', 'is_group_admin', 'is_deleted', 'last_active') as $field) {
      $user->$field = isset($item->$field) ? $item->$field : null;
    }
    $user->user_id = $item->id;
    $user->created = isset($item->created) ? strtotime($item->created) : null;
    $user->status = isset($item->presence, $item->presence->status) ? $item->presence->status : null;
    $user->status_message = isset($item->presence, $item->presence->show) ? $item->presence->show : null;
    return $user;
  }

  protected function items_to_history($response) {
    $history = array();
    foreach ($response->items as $item) {
      $entry = new \stdClass();
      $entry->date = $item->date;
      $entry->message = $item->message;
      $from = new \stdClass();
      $from->user_id = isset($item->from->id) ? $item->from->id : null;
      $from->name = isset($item->from->name) ? $item->from->name : null;
      $entry->from = $from;
      $history[] = $entry;
    }
    return $history;
  }

}
