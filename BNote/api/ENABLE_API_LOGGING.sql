-- Enable API Detailed Logging
-- Run this SQL to enable detailed logging of all API calls and responses

INSERT INTO configuration (param, value, is_active) 
VALUES ('api_detailed_logging', '1', 1)
ON DUPLICATE KEY UPDATE value = '1', is_active = 1;

-- To disable: UPDATE configuration SET value = '0' WHERE param = 'api_detailed_logging';
-- Logs are stored in: /log/api/api_YYYY-MM-DD.log
