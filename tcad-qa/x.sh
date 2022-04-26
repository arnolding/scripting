#!/bin/sh

cmd="/sbin/zfs"
usage()
{
	echo "Usage:"
	echo "- create: zfsbak DATASET [ROTATION_CNT]"
	echo "- list: zfsbak -l|--list [DATASET|ID|DATASET ID]"
	echo "- delete: zfsbak -d|--delete [DATASET|ID|DATASET ID]"
	echo "- export: zfsbak -e|--export DATASET [ID]"
	echo "- import: zfsbak -i|--import FILENAME DATASET"
	exit 1
}

create()
{
	dataset=$1
	cnt=$2
	echo ${dataset}@1
	zfs snapshot ${dataset}@1
	#sscnt=`zfs list -H -t snapshot -o name -S creation -r $dataset | wc -l | awk '{print $1}'`
	sscnt=`zfs list -t snapshot -r $dataset | wc -l |awk '{print $1}'`
	#zfs list -H -t snapshot -o name -S creation -r $1 | tail -10 | xargs -n 1 zfs  destroy
	echo create
	echo $sscnt
}

delete()
{
echo "A"	
}

if [ -z $1 ]; then
	usage
fi

if [ $1 == "-l" ]; then
	list
elif [ $1 == "--list" ]; then
	list
elif [ $1 == "-d" ]; then
	delete
elif [ $1 == "--delete" ]; then
	delete
else
	create $1
fi
