---
name: pr-review-response
description: Respond to GitHub Pull Request review comments. Use when addressing reviewer feedback, replying to review threads, or making fixes based on PR review. Handles GraphQL API for thread replies.
---

# PR Review Response Skill

## When to Use

- Addressing reviewer feedback on a PR
- Need to reply to review comments/threads
- Making fixes based on PR review and confirming resolution

## CRITICAL Rules

1. **Each comment gets an INDIVIDUAL response** - NO summary comments
2. **Always use `| cat`** on all `gh` commands to prevent pager hang
3. **Reference specific commits** when describing fixes

## Workflow

### Step 1: Get Review Threads

```bash
gh api graphql -f query='
query {
  repository(owner: "SilverAssist", name: "contact-form-to-api") {
    pullRequest(number: PR_NUMBER) {
      reviewThreads(first: 50) {
        nodes {
          id
          path
          line
          isResolved
          comments(first: 5) {
            nodes {
              id
              body
              author { login }
            }
          }
        }
      }
    }
  }
}' | cat
```

### Step 2: Analyze Each Comment

For each thread, determine:

- **Valid issue**: Make code fix, note what changed
- **Already correct**: Prepare explanation
- **Won't fix**: Prepare justification

### Step 3: Make Fixes and Commit

```bash
# Make all code changes
git add -A
git commit -m "fix: Address PR review comments"
git push
```

### Step 4: Reply to EACH Thread Individually

```bash
# Reply to thread 1
gh api graphql -f query='
mutation {
  addPullRequestReviewThreadReply(input: {
    pullRequestReviewThreadId: "PRRT_kwDONqY9Pc6XXXXX",
    body: "Fixed in commit abc1234. Added the missing backslash prefix."
  }) {
    comment { id }
  }
}' | cat

# Reply to thread 2 (separate mutation)
gh api graphql -f query='
mutation {
  addPullRequestReviewThreadReply(input: {
    pullRequestReviewThreadId: "PRRT_kwDONqY9Pc6YYYYY",
    body: "Already correct. The class was intentionally omitted because..."
  }) {
    comment { id }
  }
}' | cat
```

## Response Templates

**For fixed issues**:

```
Fixed in commit <short-sha>. <Brief description of the fix>.
```

**For already correct code**:

```
Already correct. <Explanation of why current implementation is correct>.
```

**For won't fix**:

```
Won't fix. <Justification for keeping current implementation>.
```

## Important Notes

- Thread IDs start with `PRRT_` (from GraphQL query)
- Always verify responses were posted
- Each response closes/resolves that specific thread
