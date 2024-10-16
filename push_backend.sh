excludes=(
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
  yml_export.xml
  sitemap.xml
  bitrix/tmp
)

Path_From=./api/
Path_To=root@31.128.46.89:/root/backend2/api

exclude_str="rsync -avz -e 'ssh -i ~/.ssh/id_rsa' $Path_From $Path_To"

for exclude in "${excludes[@]}"; do
    exclude_str+=" --exclude=$exclude "
done


eval $exclude_str
