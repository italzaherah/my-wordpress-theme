#!/usr/bin/env bash
# Clone the other half of the plugin/theme pair: the branch with the same name
# when it exists (paired PRs), otherwise the default branch. A private paired
# repository needs a PAIRED_REPO_TOKEN secret with read access; without it the
# cross-repository checks are skipped with a warning instead of failing.
#
# Usage: clone-paired.sh <branch> <destination>
set -uo pipefail

branch=$1
dest=$2
: "${PAIRED_REPO:?}"

if [ -n "${PAIRED_REPO_TOKEN:-}" ]; then
	url="https://x-access-token:${PAIRED_REPO_TOKEN}@github.com/${PAIRED_REPO}.git"
else
	url="https://github.com/${PAIRED_REPO}.git"
fi

export GIT_TERMINAL_PROMPT=0
if git clone --quiet --depth 1 --branch "$branch" "$url" "$dest" 2>/dev/null; then
	echo "paired repository ${PAIRED_REPO}@${branch}"
elif git clone --quiet --depth 1 "$url" "$dest" 2>/dev/null; then
	echo "paired repository ${PAIRED_REPO}@default branch"
else
	echo "::warning::paired repository ${PAIRED_REPO} is not reachable (private? add a PAIRED_REPO_TOKEN secret); cross-repository checks skipped"
fi
exit 0
