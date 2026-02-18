-- Add Notification Templates for Community Chat
INSERT INTO notification_templates (template_key, template_title, template_message, notification_type, created_at, updated_at)
VALUES 
('community_welcome', 'Welcome to {group_name}', 'You have been added to {group_name}.', 'general', NOW(), NOW()),
('community_removed', 'Removed from Community', 'You have been removed from {group_name} because your stay has ended.', 'general', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
template_title = VALUES(template_title),
template_message = VALUES(template_message),
updated_at = NOW();
