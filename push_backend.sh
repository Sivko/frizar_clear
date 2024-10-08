excludes=(  .DS_Store
  .DS_Store
  /bitrix/.settings.php
  bitrix/backup
  bitrix/cache/
  bitrix/catalog_export
  .git/
  /bitrix/managed_cache
  /bitrix/stack_cache
  /bitrix/php_inteface/dbconn.php
  /.vscode
  /upload
)

Path_From=./
Path_To=root@31.128.46.89:/root/backend/

exclude_str="rsync -avz -e 'ssh -i ~/.ssh/id_rsa' $Path_From $Path_To"

for exclude in "${excludes[@]}"; do
    exclude_str+=" --exclude=$exclude "
done


eval $exclude_str
