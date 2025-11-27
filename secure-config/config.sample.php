<?php
/**
 * Copy this file to config.php and update the values for your deployment.
 * 
 * Get your Cal.com API key from: Settings > Security > API Keys
 * Get your event slug from your Cal.com event URL (e.g., https://cal.com/username/30min -> slug is "30min")
 */

define('CALCOM_API_KEY', 'your_calcom_api_key_here');
define('CALCOM_EVENT_SLUG', '30min'); // The slug from your event URL
define('CALCOM_API_BASE_URL', 'https://api.cal.com/v1');
define('DEFAULT_TIMEZONE', 'UTC'); // Optional fallback timezone

