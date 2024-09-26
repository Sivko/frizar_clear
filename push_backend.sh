excludes=(
  bitrix/license_key.php
  bitrix/.settings.php
  upload/
  .DS_Store
  .gitignore
  bitrix/backup/
  bitrix/cache/
  bitrix/catalog_export
  .git/
  /bitrix/managed_cache
  /.vscode
)

Path_From=./api/
Path_To=root@85.193.83.186:/root/bitrix/api

exclude_str="rsync -avz -e 'ssh -i ~/.ssh/id_rsa' $Path_From $Path_To"

for exclude in "${excludes[@]}"; do
    exclude_str+=" --exclude=$exclude "
done


eval $exclude_str
