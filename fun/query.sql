SELECT *
FROM file
INNER JOIN file_shares ON file_shares.file_id = file.id
WHERE file.created_by = 1
ORDER BY file_shares.created_at DESC AND file.id DESC
