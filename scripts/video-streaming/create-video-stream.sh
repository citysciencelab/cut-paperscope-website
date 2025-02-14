#!/bin/bash
#HLS, Dash and fallback code from zazu.berlin 2020, Version 20200424
#https://blog.zazu.berlin/internet-programmierung/mpeg-dash-and-hls-adaptive-bitrate-streaming-with-ffmpeg.html


####################################
# CONFIG
####################################

VIDEO_IN=file.mp4
FOLDER=$1
VIDEO_OUT=master
HLS_TIME=4
FPS=25
GOP_SIZE=100
CRF_P=21
PRESET_P=veryslow
V_SIZE_1=960x540
V_SIZE_2=416x234
V_SIZE_3=640x360
V_SIZE_4=768x432
V_SIZE_5=1280x720
V_SIZE_6=1920x1080


####################################
# HLS
####################################

rm -rf $FOLDER/hls
mkdir -p $FOLDER/hls

ffmpeg -i $VIDEO_IN -y \
    -preset $PRESET_P -keyint_min $GOP_SIZE -g $GOP_SIZE -sc_threshold 0 -r $FPS -c:v libx264 -pix_fmt yuv420p -crf $CRF_P\
    -map v:0 -s:0 $V_SIZE_2 -b:v:0 145k -maxrate:0 155k -bufsize:0 220k \
    -map v:0 -s:1 $V_SIZE_3 -b:v:1 365k -maxrate:1 390k -bufsize:1 640k \
    -map v:0 -s:2 $V_SIZE_4 -b:v:2 730k -maxrate:2 781k -bufsize:2 1278k \
    -map v:0 -s:3 $V_SIZE_5 -b:v:3 3M -maxrate:3 3.21M -bufsize:3 5.5M \
    -map v:0 -s:4 $V_SIZE_6 -b:v:4 6M -maxrate:4 6.42M -bufsize:4 11M \
    -map a:0 -map a:0 -map a:0 -map a:0 -map a:0 -c:a aac -b:a 128k -ac 1 -ar 44100\
    -f hls -hls_time $HLS_TIME -hls_playlist_type vod -hls_flags independent_segments \
    -master_pl_name $VIDEO_OUT.m3u8 \
    -hls_segment_filename $FOLDER/hls/stream_%v/s%06d.ts \
    -strftime_mkdir 1 \
    -var_stream_map "v:0,a:0 v:1,a:1 v:2,a:2 v:3,a:3 v:4,a:4" $FOLDER/hls/stream_%v.m3u8


# remove wrong subfolder from index files
declare -a StringArray=("hls/stream_0.m3u8" "hls/stream_1.m3u8" "hls/stream_2.m3u8" "hls/stream_3.m3u8" "hls/stream_4.m3u8")
for val in "${StringArray[@]}"; do

    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "s/$FOLDER\/hls\///g" "$FOLDER/$val"
    else
        sed -i "s/$FOLDER\/hls\///g" "$FOLDER/$val"
    fi

done



####################################
# DASH
####################################

rm -rf $FOLDER/dash
mkdir -p $FOLDER/dash

ffmpeg -i $VIDEO_IN -y \
    -preset $PRESET_P -keyint_min $GOP_SIZE -g $GOP_SIZE -sc_threshold 0 -r $FPS -c:v libx264 -pix_fmt yuv420p -c:a aac -b:a 128k -ac 1 -ar 44100 \
    -map v:0 -s:0 $V_SIZE_2 -b:v:1 145k -maxrate:0 155k -bufsize:0 220k \
    -map v:0 -s:1 $V_SIZE_3 -b:v:2 365k -maxrate:1 390k -bufsize:1 640k \
    -map v:0 -s:2 $V_SIZE_4 -b:v:3 730k -maxrate:2 781k -bufsize:2 1278k \
    -map v:0 -s:3 $V_SIZE_5 -b:v:5 3M -maxrate:3 3.21M -bufsize:3 5.5M \
    -map v:0 -s:4 $V_SIZE_6 -b:v:7 6M -maxrate:4 6.42M -bufsize:4 11M \
    -map 0:a \
    -init_seg_name init\$RepresentationID\$.\$ext\$ -media_seg_name chunk\$RepresentationID\$-\$Number%05d\$.\$ext\$ \
    -use_template 1 -use_timeline 1  \
    -seg_duration 4 -adaptation_sets "id=0,streams=v id=1,streams=a" \
    -f dash $FOLDER/dash/dash.mpd

