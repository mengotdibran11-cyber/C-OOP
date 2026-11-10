#!/usr/bin/env bash
cd "$(dirname "$0")" || exit 1
status=0
for n in 1 2 3 4 5 6; do
    php "session$n/tests/run_tests.php" || status=1
done
exit $status
