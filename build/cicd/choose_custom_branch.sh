#!/bin/bash
#
# If you want to use a custom EE repo
# specify the variables:
#
# "m2ee_repo" and "m2ee_branch"
#
# when adding your branch to Bamboo.
# Similarly specify the variables:
#
# "m2tools_repo" and "m2tools_branch" IF you
#
# want to use your own tools repo.
#

M2EE=$bamboo_m2ee_checkout_directory
M2B2B=$bamboo_m2b2b_checkout_directory
BUILD_TOOLS=$bamboo_m2tools_checkout_directory
SAMPLE_DATA_CE=$bamboo_m2sampledata_ce_checkout_directory
SAMPLE_DATA_EE=$bamboo_m2sampledata_ee_checkout_directory
UPDATER=$bamboo_m2updater_checkout_directory

CUSTOM_EE_REPO=$bamboo_m2ee_repo
CUSTOM_EE_BRANCH=$bamboo_m2ee_branch

CUSTOM_B2B_REPO=$bamboo_m2b2b_repo
CUSTOM_B2B_BRANCH=$bamboo_m2b2b_branch

CUSTOM_TOOLS_REPO=$bamboo_m2tools_repo
CUSTOM_TOOLS_BRANCH=$bamboo_m2tools_branch

CUSTOM_SAMPLEDATA_CE_REPO=$bamboo_m2sampledata_ce_repo
CUSTOM_SAMPLEDATA_CE_BRANCH=$bamboo_m2sampledata_ce_branch

CUSTOM_SAMPLEDATA_EE_REPO=$bamboo_m2sampledata_ee_repo
CUSTOM_SAMPLEDATA_EE_BRANCH=$bamboo_m2sampledata_ee_branch

CUSTOM_UPDATER_REPO=$bamboo_m2updater_repo
CUSTOM_UPDATER_BRANCH=$bamboo_m2updater_branch

CWD=`pwd`

LOGS_HOME="${bamboo_working_directory}/logs"
LOG=$LOGS_HOME/choose_custom_branch.log
DELIMITER="|"
HEADER="repository${DELIMITER}branch${DELIMITER}commit"

#
# Create a log file to capture repo, branch, and commit details as a job artifact.
#
echo "* Creating log file - ${LOG}"
rm -rf $LOGS_HOME
mkdir --mode=777 -p $LOGS_HOME
if [ ! -f $LOG ]; then

      touch $LOG
      chmod 777 $LOG
      echo $HEADER | tee -a $LOG
fi

#
# Write out default (CE) repo details to the log
#
echo ${bamboo_planRepository_1_repositoryUrl}${DELIMITER}${bamboo_planRepository_1_branchName}${DELIMITER}${bamboo_planRepository_1_revision} | tee -a $LOG

#
# If a $CUSTOM_EE_REPO is specified and the $M2EE directory exists,
# then delete the $M2EE directory and checkout the custom one.
# This way CE only Tasks that don't need EE in Bamboo,
#  will not unnecessarily checkout the EE repo.
#
if [ -n "$CUSTOM_EE_REPO" ] && [ -e "$M2EE" ]; then

    rm -rf $M2EE

    # Add GitHub OAuth token to the repository URL
    CUSTOM_EE_REPO=${CUSTOM_EE_REPO/https:\/\//https:\/\/$bamboo_git_oauth_token:x-oauth-basic@}

    echo "* Using custom EE branch: '$CUSTOM_EE_BRANCH' - $bamboo_m2ee_repo"

    CUSTOM_EE_BRANCH_FOUND=$(git ls-remote $CUSTOM_EE_REPO $CUSTOM_EE_BRANCH)
    if [[ $CUSTOM_EE_BRANCH_FOUND != "" ]]; then
        git clone -b $CUSTOM_EE_BRANCH $CUSTOM_EE_REPO $M2EE
    else
        printf "custom EE branch does not exists"
        exit 1
    fi

    # Show clone's HEAD status
    cd $M2EE
    git show | head -4

    # Write custom repo/branch details to log
    commitid=$(git rev-parse HEAD 2>&1)
    echo ${bamboo_m2ee_repo}${DELIMITER}${CUSTOM_EE_BRANCH}${DELIMITER}${commitid} | tee -a $LOG
else
    #
    # EE repo has not been over-ridden
    # EE is not required, ensure the directory exists before writing repo details to log
    #
    if [ -d "$M2EE" ]; then
        cd $M2EE
        branchname=$(git rev-parse --abbrev-ref HEAD 2>&1)
        commitid=$(git rev-parse HEAD 2>&1)

        echo ${bamboo_planRepository_2_repositoryUrl}${DELIMITER}${branchname}${DELIMITER}${commitid} | tee -a $LOG
    fi
fi

#
# If a $CUSTOM_B2B_REPO is specified and the $M2B2B directory exists,
# then delete the $M2B2B directory and checkout the custom one.
# This way CE or EE only Tasks that don't need B2B in Bamboo,
#  will not unnecessarily checkout the B2B repo.
#
cd $CWD

if [ -n "$CUSTOM_B2B_REPO" ] && [ -e "$M2EE" ]; then

    rm -rf $M2B2B

    # Add GitHub OAuth token to the repository URL
    CUSTOM_B2B_REPO=${CUSTOM_B2B_REPO/https:\/\//https:\/\/$bamboo_git_oauth_token:x-oauth-basic@}

    echo "* Using custom B2B branch: '$CUSTOM_B2B_BRANCH' - $bamboo_m2b2b_repo"

    CUSTOM_B2B_BRANCH_FOUND=$(git ls-remote $CUSTOM_B2B_REPO $CUSTOM_B2B_BRANCH)
    if [[ $CUSTOM_B2B_BRANCH_FOUND != "" ]]; then
        git clone -b $CUSTOM_B2B_BRANCH $CUSTOM_B2B_REPO $M2B2B
    else
        printf "custom B2B branch does not exists"
        exit 1
    fi

    # Show clone's HEAD status
    cd $M2B2B
    git show | head -4

    # Write custom repo/branch details to log
    commitid=$(git rev-parse HEAD 2>&1)
    echo ${bamboo_m2b2b_repo}${DELIMITER}${CUSTOM_B2B_BRANCH}${DELIMITER}${commitid} | tee -a $LOG
else
    #
    # B2B repo has not been over-ridden
    # B2B is not required, ensure the directory exists before writing repo details to log
    #
    if [ -d "$M2B2B" ]; then
        cd $M2B2B
        branchname=$(git rev-parse --abbrev-ref HEAD 2>&1)
        commitid=$(git rev-parse HEAD 2>&1)

        echo ${bamboo_planRepository_2_repositoryUrl}${DELIMITER}${branchname}${DELIMITER}${commitid} | tee -a $LOG
    fi
fi


#
# Checkout a custom tools repo if provided.
#
cd $CWD

if [ -n "$CUSTOM_TOOLS_REPO" ]; then

    rm -rf $BUILD_TOOLS

    # Add GitHub OAuth token to the repository URL
    CUSTOM_TOOLS_REPO=${CUSTOM_TOOLS_REPO/https:\/\//https:\/\/$bamboo_git_oauth_token:x-oauth-basic@}

    echo "* Using custom Tools branch: '$CUSTOM_TOOLS_BRANCH' - $bamboo_m2tools_repo"

    CUSTOM_TOOLS_BRANCH_FOUND=$(git ls-remote $CUSTOM_TOOLS_REPO $CUSTOM_TOOLS_BRANCH)
    if [[ $CUSTOM_TOOLS_BRANCH_FOUND != "" ]]; then
        git clone -b $CUSTOM_TOOLS_BRANCH $CUSTOM_TOOLS_REPO $BUILD_TOOLS
    else
        printf "custom Tools branch does not exits"
        exit 1
    fi


    # Show clone's HEAD status
    cd $BUILD_TOOLS
    git show | head -4

    # Write custom repo/branch details to log
    commitid=$(git rev-parse HEAD 2>&1)
    echo ${bamboo_m2tools_repo}${DELIMITER}${CUSTOM_TOOLS_BRANCH}${DELIMITER}${commitid} | tee -a $LOG
else
    #
    # Build-tools (aka, infrastructure) repo has not been over-ridden
    # Ensure the directory exists before writing repo details to log
    #
    if [ -d "$BUILD_TOOLS" ]; then
        cd $BUILD_TOOLS
        branchname=$(git rev-parse --abbrev-ref HEAD 2>&1)
        commitid=$(git rev-parse HEAD 2>&1)

        echo ${bamboo_planRepository_3_repositoryUrl}${DELIMITER}${branchname}${DELIMITER}${commitid} | tee -a $LOG
    fi
fi


#
# Checkout custom sample-data-ce repo if provided.
#
cd $CWD

if [ -n "$CUSTOM_SAMPLEDATA_CE_REPO" ]; then

    rm -rf $SAMPLE_DATA_CE

    # Add GitHub OAuth token to the repository URL
    CUSTOM_SAMPLEDATA_CE_REPO=${CUSTOM_SAMPLEDATA_CE_REPO/https:\/\//https:\/\/$bamboo_git_oauth_token:x-oauth-basic@}

    echo "* Using custom sample-data-ce branch: '$CUSTOM_SAMPLEDATA_CE_BRANCH' - $bamboo_m2sampledata_ce_repo"

    CUSTOM_SAMPLEDATA_CE_BRANCH_FOUND=$(git ls-remote $CUSTOM_SAMPLEDATA_CE_REPO $CUSTOM_SAMPLEDATA_CE_BRANCH)
    if [[ $CUSTOM_SAMPLEDATA_CE_BRANCH_FOUND != "" ]]; then
        git clone -b $CUSTOM_SAMPLEDATA_CE_BRANCH $CUSTOM_SAMPLEDATA_CE_REPO $SAMPLE_DATA_CE
    else
        printf "custom sample-data-ce branch does not exits"
        exit 1
    fi

    # Show clone's HEAD status
    cd $SAMPLE_DATA_CE
    git show | head -4

    # Write custom repo/branch details to log
    commitid=$(git rev-parse HEAD 2>&1)
    echo ${bamboo_m2sampledata_ce_repo}${DELIMITER}${CUSTOM_SAMPLEDATA_CE_BRANCH}${DELIMITER}${commitid} | tee -a $LOG
else
    #
    # Sample-data-ce repo has not been over-ridden
    # Ensure the directory exists before writing repo details to log
    #
    if [ -d "$SAMPLE_DATA_CE" ]; then
        cd $SAMPLE_DATA_CE
        branchname=$(git rev-parse --abbrev-ref HEAD 2>&1)
        commitid=$(git rev-parse HEAD 2>&1)

        echo ${bamboo_planRepository_4_repositoryUrl}${DELIMITER}${branchname}${DELIMITER}${commitid} | tee -a $LOG
    fi
fi


#
# Checkout custom sample-data-ee repo if provided.
#
cd $CWD

if [ -n "$CUSTOM_SAMPLEDATA_EE_REPO" ]; then

    rm -rf $SAMPLE_DATA_EE

    # Add GitHub OAuth token to the repository URL
    CUSTOM_SAMPLEDATA_EE_REPO=${CUSTOM_SAMPLEDATA_EE_REPO/https:\/\//https:\/\/$bamboo_git_oauth_token:x-oauth-basic@}

    echo "* Using custom sample-data-ee branch: '$CUSTOM_SAMPLEDATA_EE_BRANCH' - $bamboo_m2sampledata_ee_repo"

    CUSTOM_SAMPLEDATA_EE_BRANCH_FOUND=$(git ls-remote $CUSTOM_SAMPLEDATA_EE_REPO $CUSTOM_SAMPLEDATA_EE_BRANCH)
    if [[ $CUSTOM_SAMPLEDATA_EE_BRANCH_FOUND != "" ]]; then
        git clone -b $CUSTOM_SAMPLEDATA_EE_BRANCH $CUSTOM_SAMPLEDATA_EE_REPO $SAMPLE_DATA_EE
    else
        printf "custom sample-data-ee branch does not exits"
        exit 1
    fi

    # Show clone's HEAD status
    cd $SAMPLE_DATA_EE
    git show | head -4

    # Write custom repo/branch details to log
    commitid=$(git rev-parse HEAD 2>&1)
    echo ${bamboo_m2sampledata_ee_repo}${DELIMITER}${CUSTOM_SAMPLEDATA_EE_BRANCH}${DELIMITER}${commitid} | tee -a $LOG
else
    #
    # Sample-data-ee repo has not been over-ridden
    # Ensure the directory exists before writing repo details to log
    #
    if [ -d "$SAMPLE_DATA_EE" ]; then
        cd $SAMPLE_DATA_EE
        branchname=$(git rev-parse --abbrev-ref HEAD 2>&1)
        commitid=$(git rev-parse HEAD 2>&1)

        echo ${bamboo_planRepository_4_repositoryUrl}${DELIMITER}${branchname}${DELIMITER}${commitid} | tee -a $LOG
    fi
fi


#
# Checkout custom updater repo if provided.
#
cd $CWD

if [ -n "$CUSTOM_UPDATER_REPO" ]; then

    rm -rf $UPDATER

    # Add GitHub OAuth token to the repository URL
    CUSTOM_UPDATER_REPO=${CUSTOM_UPDATER_REPO/https:\/\//https:\/\/$bamboo_git_oauth_token:x-oauth-basic@}

    echo "* Using custom updater branch: '$CUSTOM_UPDATER_BRANCH' - $bamboo_m2updater_repo"

    CUSTOM_UPDATER_BRANCH_FOUND=$(git ls-remote $CUSTOM_UPDATER_REPO $CUSTOM_UPDATER_BRANCH)
    if [[ $CUSTOM_UPDATER_BRANCH_FOUND != "" ]]; then
        git clone -b $CUSTOM_UPDATER_BRANCH $CUSTOM_UPDATER_REPO $UPDATER
    else
        printf "custom updater branch does not exists"
        exit 1
    fi


    # Show clone's HEAD status
    cd $UPDATER
    git show | head -4

    # Write custom repo/branch details to log
    commitid=$(git rev-parse HEAD 2>&1)
    echo ${bamboo_m2updater_repo}${DELIMITER}${CUSTOM_UPDATER_BRANCH}${DELIMITER}${commitid} | tee -a $LOG
else
    #
    # Updater repo has not been over-ridden
    # Ensure the directory exists before writing repo details to log
    #
    if [ -d "$UPDATER" ]; then
        cd $UPDATER
        branchname=$(git rev-parse --abbrev-ref HEAD 2>&1)
        commitid=$(git rev-parse HEAD 2>&1)

        echo ${bamboo_planRepository_5_repositoryUrl}${DELIMITER}${branchname}${DELIMITER}${commitid} | tee -a $LOG
    fi
fi

cd $CWD
