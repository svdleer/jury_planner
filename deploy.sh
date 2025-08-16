#!/bin/bash

# Automated Deployment Script for Jury Planner
# This script commits changes and deploys to production

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting Jury Planner Deployment...${NC}"

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: Not in a git repository${NC}"
    exit 1
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}📝 Found uncommitted changes${NC}"
    
    # Get commit message from user or use default
    if [ -z "$1" ]; then
        echo -e "${BLUE}💬 Enter commit message (or press Enter for default):${NC}"
        read commit_message
        if [ -z "$commit_message" ]; then
            commit_message="Auto-deploy: $(date '+%Y-%m-%d %H:%M:%S')"
        fi
    else
        commit_message="$1"
    fi
    
    echo -e "${GREEN}📦 Adding all changes...${NC}"
    git add .
    
    echo -e "${GREEN}💾 Committing with message: '$commit_message'${NC}"
    git commit -m "$commit_message"
else
    echo -e "${GREEN}✅ No uncommitted changes found${NC}"
fi

# Push to origin (GitHub)
echo -e "${BLUE}🌐 Pushing to origin (GitHub)...${NC}"
if git push origin main; then
    echo -e "${GREEN}✅ Successfully pushed to origin${NC}"
else
    echo -e "${RED}❌ Failed to push to origin${NC}"
    exit 1
fi

# Push to production server
echo -e "${BLUE}🏭 Deploying to production server...${NC}"
if git push production main; then
    echo -e "${GREEN}✅ Successfully deployed to production${NC}"
else
    echo -e "${YELLOW}⚠️  Warning: Failed to push to production. This might be the first push.${NC}"
    echo -e "${BLUE}🔧 Trying to set upstream and push...${NC}"
    if git push -u production main; then
        echo -e "${GREEN}✅ Successfully deployed to production with upstream${NC}"
    else
        echo -e "${RED}❌ Failed to deploy to production${NC}"
        echo -e "${YELLOW}💡 You may need to initialize the repository on the production server first${NC}"
        exit 1
    fi
fi

# Show deployment summary
echo -e "\n${GREEN}🎉 Deployment Summary:${NC}"
echo -e "${GREEN}├── Origin (GitHub): ✅ Updated${NC}"
echo -e "${GREEN}├── Production: ✅ Deployed${NC}"
echo -e "${GREEN}└── Timestamp: $(date)${NC}"

echo -e "\n${BLUE}🌍 Your PHP interface should now be available on your production server!${NC}"
echo -e "${YELLOW}💡 Don't forget to run the database test: https://your-domain.com/php_interface/test_connection.php${NC}"
