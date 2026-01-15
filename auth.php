<?php

/**
 * Telegram Unified Authentication
 *
 * Unified verification for:
 *  - Telegram Login Widget
 *  - Telegram Mini Apps (WebApp)
 *
 * Supports:
 *  - hash validation
 *  - auth_date expiration check
 *  - automatic source detection (widget vs mini app)
 *  - unified user data extraction
 *
 * PHP version: 8.0+
 *
 * @author    Nipaa (GitHub: Makareene)
 * @copyright 2026 Nipaa
 * @license   MIT
 * @version   1.0.0
 * @note      GitHub profile linked to uniquely identify the author
 */

class TelegramUnifiedAuth {

  /**
   * Telegram Bot token from BotFather
   *
   * Used to validate Telegram signatures.
   *
   * @var string
   */
  private string $token = 'PUT_YOUR_TG_TOKEN';

  /**
   * Constructor
   *
   * Allows setting the Telegram Bot token dynamically.
   *
   * @param string|null $token Telegram Bot token
   */
  function __construct(?string $token = null){
    if (!is_null($token) && $token !== '') {
      $this->token = $token;
    }
  }

  /**
   * Detects whether incoming data belongs to a Telegram Mini App
   *
   * Heuristic logic:
   *  - Mini App does NOT contain "id" in root
   *  - Contains "user" key as JSON string
   *  - Decoded user object must contain "id"
   *
   * @param array $data Incoming Telegram data
   * @return bool
   */
  private function is_miniapp(array $data): bool {
    if (!empty($data['id'])) return false;
    if (empty($data['user']) || !is_string($data['user'])) return false;

    $user = json_decode($data['user'], true);
    return is_array($user) && isset($user['id']);
  }

  /**
   * Converts query string data into array (in-place)
   *
   * Used for Telegram.WebApp.initData which is passed
   * as a query string.
   *
   * @param array|string $data
   * @return void
   */
  private function to_array(array|string &$data): void {
    if (is_string($data)) {
      parse_str($data, $data);
    }
  }

  /**
   * Validates Telegram authentication data
   *
   * Performs:
   *  - data normalization
   *  - auth_date expiration check
   *  - signature validation (widget or mini app)
   *
   * Return values:
   *  - true  → valid authentication
   *  - false → invalid or malformed data
   *  - 0     → authentication data expired
   *
   * @param array|string $data Telegram auth data
   * @return bool|int
   */
  function check(array|string $data): int|bool {
    $this->to_array($data);

    // Required fields
    if (empty($data['hash']) || empty($data['auth_date'])) {
      return false;
    }

    // Expiration check (5 minutes)
    if (time() - (int)$data['auth_date'] > 300) {
      return 0;
    }

    // Extract hash
    $hash = $data['hash'];
    unset($data['hash']);

    // Sort fields alphabetically
    ksort($data);

    // Build data-check-string
    $check_string = '';
    foreach ($data as $key => $value) {
      $check_string .= $key . '=' . $value . "\n";
    }
    $check_string = rtrim($check_string, "\n");

    // Generate secret key depending on source
    $secret_key = $this->is_miniapp($data)
      ? hash_hmac('sha256', $this->token, 'WebAppData', true)
      : hash('sha256', $this->token, true);

    // Calculate signature
    $calculated_hash = hash_hmac('sha256', $check_string, $secret_key);

    return hash_equals($calculated_hash, $hash);
  }

  /**
   * Extracts normalized Telegram user data
   *
   * Returns unified user array regardless of
   * whether the source is a widget or mini app.
   *
   * @param array|string $data Telegram auth data
   * @return array{id:int,first_name:string,last_name:string,username:string,photo_url:string}
   */
  function get(array|string $data): array {
    $this->to_array($data);

    // Extract user object
    $user = $this->is_miniapp($data)
      ? json_decode($data['user'], true)
      : $data;

    return [
      'id'         => isset($user['id']) ? (int)$user['id'] : 0,
      'first_name' => $user['first_name'] ?? '',
      'last_name'  => $user['last_name'] ?? '',
      'username'   => $user['username'] ?? '',
      'photo_url'  => $user['photo_url'] ?? ''
    ];
  }

}

?>