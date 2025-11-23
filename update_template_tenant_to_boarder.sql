-- Update notification template to change {tenant_name} to {boarder_name}
-- This updates the existing template in the database
-- Run this SQL script to update your notification_templates table

UPDATE notification_templates 
SET template_message = REPLACE(template_message, '{tenant_name}', '{boarder_name}')
WHERE template_key = 'booking_created' 
AND template_message LIKE '%{tenant_name}%';

-- Verify the update
SELECT template_key, template_title, template_message 
FROM notification_templates 
WHERE template_key = 'booking_created';

