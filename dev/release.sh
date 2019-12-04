#!/usr/bin/env bash

GREEN='\033[0;32m'
RED='\e[31m'
NC='\033[0m'

set -e

printf "\n${GREEN}You are about the start a release process${NC}\n"

printf "\n${RED}[ACTION]${NC} Changes on changelog are not part of this release process.\n"
read -p "is the changelog modified and commited separately? If yes, are you sure? (y/n): "
if [[ ! $REPLY =~ ^[Yy]$ ]]; then exit 1; fi

releaseBranch='master'
currentBranch=`git rev-parse --abbrev-ref HEAD` 

if [ "$currentBranch" != "$releaseBranch" ]; then
  printf "\n${RED}[ERROR]${NC} You must be on master.\n"
  exit 1
fi

changes=`echo $(git add . && git diff --cached --numstat | wc -l) | sed 's/ *$//g'`

if [[ "$changes" != "0" ]]; then
  printf "\n${RED}[ERROR]${NC} Working tree is not clean.\n"
  exit 1
fi

printf "${GREEN}[INFO]${NC} Update working tree.\n"
git pull origin $releaseBranch
git fetch origin --tags

# todo keep working on this

