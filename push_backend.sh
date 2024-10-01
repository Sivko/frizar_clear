excludes=(
  .DS_Store
  .gitignore
  bitrix/backup/
  bitrix/.settings.php
  bitrix/cache/
  bitrix/catalog_export
  .git/
  /bitrix/managed_cache
  /bitrix/php_inteface/dbconn.php
  /.vscode
)

Path_From=root@85.193.83.186:/root/frizar_backend
Path_To=./

exclude_str="rsync -avz -e 'ssh -i ~/.ssh/id_rsa' $Path_From $Path_To"

for exclude in "${excludes[@]}"; do
    exclude_str+=" --exclude=$exclude "
done


eval $exclude_str
