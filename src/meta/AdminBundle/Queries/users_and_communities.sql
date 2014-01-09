-- Select all users, not deleted, and list their communities

SELECT 
  u.`username` AS Username, 
  u.`email` AS Email, 
  u.`created_at` AS Date_Création, 
  u.`last_seen_at` AS Dernière_Connection, 
  IF(u.`enableDigest` IS NULL OR u.`enableDigest` = 0, "", "Oui") AS Activation_Mails, 
  GROUP_CONCAT(c.`name`) AS Communautés
FROM 
  User u
JOIN 
  User_in_community uc ON uc.`user_id` = u.`id`
JOIN
  Community c ON c.`id` = uc.`community_id`
WHERE u.`deleted_at`IS NULL
GROUP BY u.`username` -- For GROUP_CONCAT
ORDER BY u.`last_seen_at`