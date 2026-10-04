# Shared helpers for bin/server-* scripts. Sourced, not executed.
#
# Connection: the `findo` alias in ~/.ssh/config (key auth). Override with
# FINDO_SSH_HOST if your alias differs.
#
# Model: the `production` branch is exactly the code that is on the server.
#   server-pull    reads the server, commits its state to `production`, merges it.
#   server-status  compares the server against `production` (read-only).
#   server-deploy  ships HEAD, then moves `production` to HEAD.

set -euo pipefail

FINDO_SSH_HOST="${FINDO_SSH_HOST:-findo}"
REMOTE_APP="/home/findogroup.com/app"
REMOTE_STATE="/home/findogroup.com/.deploy"
REMOTE_PHP="/usr/local/lsws/lsphp84/bin/php"
PRODUCTION_BRANCH="production"
SITE_URL="https://findogroup.com/"

REPO_ROOT="$(git -C "$(dirname "${BASH_SOURCE[0]}")" rev-parse --show-toplevel)"

die() { printf '\033[31m✗ %s\033[0m\n' "$*" >&2; exit 1; }
info() { printf '\033[36m→ %s\033[0m\n' "$*" >&2; }
ok() { printf '\033[32m✓ %s\033[0m\n' "$*" >&2; }
warn() { printf '\033[33m! %s\033[0m\n' "$*" >&2; }

# The "code scope": files that belong in git. Everything else on the server
# (vendor, uploads, storage, build output, .env, caches) is runtime state.
# The same function runs on the server and locally so both sides agree.
SCOPE_FN=$(cat <<'EOF'
scope_files() {
    ( cd "$1" && find . \( \
        -path ./.git -o -path ./vendor -o -path ./node_modules -o -path ./storage \
        -o -path ./bootstrap/cache -o -path ./public/build -o -path ./public/wp-content \
        -o -path ./public/media -o -path ./public/storage -o -path ./public/hot \
        -o -path ./public/.well-known -o -path ./public/findo-profile-v2.pdf \
        -o -path ./.server-data -o -path ./.codex -o -path ./.cursor -o -path ./.idea \
        -o -path ./.vscode -o -path ./.claude -o -name .env -o \( -name '.env.*' ! -name .env.example \) \
        -o -name '*.log' -o -name '*.sqlite*' -o -name .DS_Store -o -name Thumbs.db -o -name .phpunit.result.cache \
    \) -prune -o \( -type f -o -type l \) -print | sed 's|^\./||' | LC_ALL=C sort )
}
# "<sha256><2 chars><path>" lines; the separator differs per platform, so
# readers use substr(line, 1, 64) / substr(line, 67).
scope_hashes() { scope_files "$1" | (cd "$1" && xargs -r -d '\n' sha256sum); }
EOF
)
eval "$SCOPE_FN"

# Run a bash script (stdin) on the server. Prelude only defines variables and
# functions; it does not touch the filesystem.
remote() {
    {
        printf 'set -euo pipefail\nAPP=%q\nSTATE=%q\nPHP=%q\nSITE_URL=%q\n' \
            "$REMOTE_APP" "$REMOTE_STATE" "$REMOTE_PHP" "$SITE_URL"
        printf '%s\n' "$SCOPE_FN"
        printf 'as_findo() { runuser -u findo -- env HOME=/home/findogroup.com "$@"; }\n'
        cat
    } | ssh -o BatchMode=yes -o LogLevel=ERROR "$FINDO_SSH_HOST" 'bash -s'
}

require_clean_tree() {
    [[ -z "$(git -C "$REPO_ROOT" status --porcelain)" ]] \
        || die "Working tree has uncommitted changes. Commit or stash them first."
}

# Make sure a local `production` branch exists (fresh clones only have origin/production).
ensure_production_branch() {
    git -C "$REPO_ROOT" show-ref --verify --quiet "refs/heads/$PRODUCTION_BRANCH" && return
    if git -C "$REPO_ROOT" show-ref --verify --quiet "refs/remotes/origin/$PRODUCTION_BRANCH"; then
        git -C "$REPO_ROOT" branch -q "$PRODUCTION_BRANCH" "origin/$PRODUCTION_BRANCH"
    else
        die "No '$PRODUCTION_BRANCH' branch. Run: git fetch origin $PRODUCTION_BRANCH"
    fi
}

# Print M/A/D lines for server files that differ from the `production` branch.
# Read-only on the server.
server_drift() {
    local tmp
    tmp="$(mktemp -d)"
    mkdir "$tmp/tree"
    git -C "$REPO_ROOT" archive "$PRODUCTION_BRANCH" | tar -x -C "$tmp/tree"
    scope_hashes "$tmp/tree" > "$tmp/expected"
    remote > "$tmp/actual" <<'EOF'
scope_hashes "$APP"
EOF
    awk 'FNR==NR { m[substr($0,67)] = substr($0,1,64); next }
         { p = substr($0,67); h = substr($0,1,64)
           if (!(p in m)) print "A " p; else if (m[p] != h) print "M " p
           delete m[p] }
         END { for (p in m) print "D " p }' "$tmp/expected" "$tmp/actual" | LC_ALL=C sort -k2
    rm -rf "$tmp"
}
