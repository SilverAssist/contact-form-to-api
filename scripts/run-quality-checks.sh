#!/usr/bin/env bash

###############################################################################
# Contact Form 7 to API - Quality Checks Script
#
# This script runs all quality checks for the plugin following SilverAssist
# WordPress Plugin Development Standards. It ensures code quality through:
#
# 1. Composer validation
# 2. PHP CodeSniffer (WordPress-Extra)
# 3. PHPStan static analysis (Level 8)
# 4. PHPUnit test suite
#
# Exit codes:
#   0 - All checks passed
#   1 - One or more checks failed
#
# Usage:
#   ./scripts/run-quality-checks.sh
#   ./scripts/run-quality-checks.sh --fix      # Auto-fix PHPCS issues
#   ./scripts/run-quality-checks.sh --verbose  # Verbose output
#
# @package SilverAssist\ContactFormToAPI
# @since 1.0.0
# @author Silver Assist
###############################################################################

set -e
set -u
set -o pipefail

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m' # No Color
readonly BOLD='\033[1m'

# Script directory
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Flags
FIX_MODE=false
VERBOSE_MODE=false
FAILED_CHECKS=()

###############################################################################
# Functions
###############################################################################

# Print colored output
print_header() {
    echo -e "${BOLD}${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}${BLUE}  $1${NC}"
    echo -e "${BOLD}${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Record failed check
record_failure() {
    FAILED_CHECKS+=("$1")
}

###############################################################################
# 1. Composer Validation
###############################################################################
check_composer() {
    print_header "1/4 - Composer Validation"
    
    if ! command_exists composer; then
        print_error "Composer is not installed"
        record_failure "Composer"
        return 1
    fi
    
    cd "$PROJECT_DIR"
    
    if [[ "$VERBOSE_MODE" == true ]]; then
        composer validate --strict --verbose
    else
        composer validate --strict
    fi
    
    local exit_code=$?
    
    if [[ $exit_code -eq 0 ]]; then
        print_success "Composer validation passed"
        return 0
    else
        print_error "Composer validation failed"
        record_failure "Composer"
        return 1
    fi
}

###############################################################################
# 2. PHP CodeSniffer (PHPCS)
###############################################################################
check_phpcs() {
    print_header "2/4 - PHP CodeSniffer (WordPress-Extra)"
    
    cd "$PROJECT_DIR"
    
    if [[ ! -f "vendor/bin/phpcs" ]]; then
        print_error "PHPCS not found. Run: composer install"
        record_failure "PHPCS"
        return 1
    fi
    
    local phpcs_cmd="vendor/bin/phpcs"
    local phpcs_args=""
    
    if [[ "$VERBOSE_MODE" == true ]]; then
        phpcs_args="--verbose"
    fi
    
    if [[ "$FIX_MODE" == true ]]; then
        print_info "Running PHPCBF to auto-fix issues..."
        if vendor/bin/phpcbf $phpcs_args; then
            print_success "Auto-fix completed"
        else
            # PHPCBF returns 1 if it fixed files, 0 if nothing to fix
            print_warning "Some issues were fixed, re-running PHPCS..."
        fi
    fi
    
    if $phpcs_cmd $phpcs_args; then
        print_success "PHPCS check passed"
        return 0
    else
        print_error "PHPCS found coding standard violations"
        print_info "Run with --fix to auto-fix issues: ./scripts/run-quality-checks.sh --fix"
        record_failure "PHPCS"
        return 1
    fi
}

###############################################################################
# 3. PHPStan Static Analysis
###############################################################################
check_phpstan() {
    print_header "3/4 - PHPStan Static Analysis (Level 8)"
    
    cd "$PROJECT_DIR"
    
    if [[ ! -f "vendor/bin/phpstan" ]]; then
        print_error "PHPStan not found. Run: composer install"
        record_failure "PHPStan"
        return 1
    fi
    
    local phpstan_cmd="vendor/bin/phpstan analyse"
    local phpstan_args=""
    
    if [[ "$VERBOSE_MODE" == true ]]; then
        phpstan_args="--verbose"
    fi
    
    if $phpstan_cmd $phpstan_args; then
        print_success "PHPStan analysis passed"
        return 0
    else
        print_error "PHPStan found type safety issues"
        print_info "Review the errors above and fix them manually"
        record_failure "PHPStan"
        return 1
    fi
}

###############################################################################
# 4. PHPUnit Tests
###############################################################################
check_phpunit() {
    print_header "4/4 - PHPUnit Test Suite"
    
    cd "$PROJECT_DIR"
    
    if [[ ! -f "vendor/bin/phpunit" ]]; then
        print_error "PHPUnit not found. Run: composer install"
        record_failure "PHPUnit"
        return 1
    fi
    
    # Check if WordPress test environment is configured
    if [[ ! -f "tests/bootstrap.php" ]]; then
        print_warning "WordPress test bootstrap not found"
        print_info "Skipping PHPUnit tests (test environment not configured)"
        return 0
    fi
    
    local phpunit_cmd="vendor/bin/phpunit"
    local phpunit_args=""
    
    if [[ "$VERBOSE_MODE" == true ]]; then
        phpunit_args="--verbose"
    fi
    
    if $phpunit_cmd $phpunit_args; then
        print_success "All tests passed"
        return 0
    else
        print_error "Some tests failed"
        print_info "Review the test output above"
        record_failure "PHPUnit"
        return 1
    fi
}

###############################################################################
# Summary
###############################################################################
print_summary() {
    echo ""
    print_header "Quality Checks Summary"
    
    if [[ ${#FAILED_CHECKS[@]} -eq 0 ]]; then
        print_success "All quality checks passed! ✨"
        echo ""
        return 0
    else
        print_error "Failed checks: ${#FAILED_CHECKS[@]}"
        for check in "${FAILED_CHECKS[@]}"; do
            echo -e "  ${RED}✗${NC} $check"
        done
        echo ""
        return 1
    fi
}

###############################################################################
# Main
###############################################################################
main() {
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --fix)
                FIX_MODE=true
                shift
                ;;
            --verbose|-v)
                VERBOSE_MODE=true
                shift
                ;;
            --help|-h)
                echo "Usage: $0 [OPTIONS]"
                echo ""
                echo "Options:"
                echo "  --fix        Auto-fix PHPCS issues with PHPCBF"
                echo "  --verbose    Enable verbose output for all checks"
                echo "  --help       Show this help message"
                echo ""
                exit 0
                ;;
            *)
                print_error "Unknown option: $1"
                echo "Use --help for usage information"
                exit 1
                ;;
        esac
    done
    
    echo ""
    print_header "Contact Form 7 to API - Quality Checks"
    echo ""
    
    if [[ "$FIX_MODE" == true ]]; then
        print_info "Running in auto-fix mode"
    fi
    
    if [[ "$VERBOSE_MODE" == true ]]; then
        print_info "Verbose mode enabled"
    fi
    
    echo ""
    
    # Run all checks
    check_composer || true
    echo ""
    
    check_phpcs || true
    echo ""
    
    check_phpstan || true
    echo ""
    
    check_phpunit || true
    echo ""
    
    # Print summary and exit with appropriate code
    if print_summary; then
        exit 0
    else
        exit 1
    fi
}

# Run main function
main "$@"
