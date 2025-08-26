#!/bin/bash

# Quick PHP Interface Deployment Script
# This script specifically deploys the PHP interface changes

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}📱 Quick PHP Interface Deployment${NC}"

# Commit message for PHP interface updates
COMMIT_MSG="Deploy PHP interface updates: $(date '+%Y-%m-%d %H:%M:%S')"

if [ ! -z "$1" ]; then
    COMMIT_MSG="PHP Interface: $1"
fi

echo -e "${GREEN}📦 Adding PHP interface changes...${NC}"
git add php_interface/
git add README.md

if git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}ℹ️  No changes to commit${NC}"
else
    echo -e "${GREEN}💾 Committing: $COMMIT_MSG${NC}"
    git commit -m "$COMMIT_MSG"
fi

echo -e "${BLUE}🚀 Deploying to production...${NC}"
git push origin main
git push production main

echo -e "${GREEN}✅ PHP Interface deployed successfully!${NC}"
