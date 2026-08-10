##############################################################################
# Developer makefile for datafield_vimipad (ViMi Pad satellite).
#
# Local pre-flight mirroring the GitHub CI, adapted for a satellite plugin:
# no React/esbuild bundle (the editor is provided by mod_vimipad); the AMD
# modules are built with Moodle's Grunt. Run from inside a Moodle checkout that
# has this plugin AND mod_vimipad installed.
#
#   make check   # lint + build + phpunit (what CI runs)
#   make fix     # auto-fix phpdoc + phpcbf, then rebuild AMD
#   make amd     # rebuild the AMD module(s) only
#
# Override paths if your layout differs, e.g.:
#   make check MOODLE_ROOT=/path/to/moodle
##############################################################################

THIS_DIR      := $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))
PLUGIN_DIR    ?= $(THIS_DIR)
MOODLE_ROOT   ?= $(abspath $(PLUGIN_DIR)/../../../..)
PLUGIN_NAME   ?= datafield_vimipad
PLUGIN_REL    ?= mod/data/field/vimipad
PHP           ?= $(shell which php 2>/dev/null || echo /usr/bin/php)
PHPCS         ?= phpcs
PHPCBF        ?= phpcbf
NPX           ?= npx

.PHONY: all fix check clear \
        lint-php fix-lint-php lint-phpdoc fix-phpdoc lint-mustache \
        lint-cpd lint-md lint-js amd build phpunit

all: clear fix check
	@echo ""
	@echo "=== All done. ==="

fix: clear fix-phpdoc fix-lint-php amd
	@echo ""
	@echo "=== All fixes complete. ==="

check: clear lint-php lint-phpdoc lint-mustache lint-cpd lint-md lint-js amd phpunit
	@echo ""
	@echo "=== All checks complete. Review output above for errors. ==="

clear:
	@clear || true

lint-php:
	@echo "=== phpcs (Moodle standard, excludes tools/) ==="
	-cd $(PLUGIN_DIR) && $(PHPCS) \
		--standard=moodle --extensions=php --severity=1 \
		--no-cache --ignore=tools/,amd/build/ .

fix-lint-php:
	@echo ""
	@echo "=== phpcbf (auto-fix) ==="
	-cd $(PLUGIN_DIR) && $(PHPCBF) \
		--standard=moodle --extensions=php --ignore=tools/,amd/build/ .

lint-phpdoc:
	@echo ""
	@echo "=== PHPDoc (local_moodlecheck) ==="
	-cd $(MOODLE_ROOT) && $(PHP) local/moodlecheck/cli/moodlecheck.php \
		--path=$(PLUGIN_REL) --format=text 2>&1 \
		| grep -B1 '    Line' | grep -v '^--$$' || true

fix-phpdoc:
	@echo ""
	@echo "=== fix_phpdoc (tools/fix_phpdoc.php) ==="
	-$(PHP) $(PLUGIN_DIR)/tools/fix_phpdoc.php $(PLUGIN_DIR)

lint-mustache:
	@echo ""
	@echo "=== Mustache syntax check ==="
	@if [ -d $(PLUGIN_DIR)/templates ]; then \
		$(PHP) $(PLUGIN_DIR)/tools/mustache_check.php \
			$(PLUGIN_DIR)/templates 2>&1 | grep -v '^OK:' || true; \
	else \
		echo "No templates/ directory - Mustache check skipped."; \
	fi

lint-cpd:
	@echo ""
	@echo "=== PHP Copy/Paste Detector ==="
	-cd $(PLUGIN_DIR) && phpcpd --min-lines 5 --min-tokens 70 \
		--exclude tools --exclude amd/build . || true

lint-md:
	@echo ""
	@echo "=== PHP Mess Detector ==="
	-cd $(PLUGIN_DIR) && phpmd . text \
		cleancode,codesize,controversial,design,naming,unusedcode \
		--exclude tests,tools || true

lint-js:
	@echo ""
	@echo "=== ESLint (skipped when amd/src/ is empty) ==="
	@if ls $(PLUGIN_DIR)/amd/src/*.js 2>/dev/null | grep -q .; then \
		cd $(MOODLE_ROOT) && $(NPX) grunt eslint --root=. \
			--files=$(PLUGIN_REL)/amd/src/ --show-lint-warnings; \
	else \
		echo "No AMD source files - ESLint skipped."; \
	fi

amd:
	@echo ""
	@echo "=== AMD rebuild (Moodle Grunt; skipped when amd/src/ is empty) ==="
	@if ls $(PLUGIN_DIR)/amd/src/*.js 2>/dev/null | grep -q .; then \
		cd $(PLUGIN_DIR) && $(NPX) grunt amd --force; \
	else \
		echo "No AMD source files - skipped."; \
	fi

build: amd
	@echo ""
	@echo "=== Front-end build complete (AMD). ==="

phpunit:
	@echo ""
	@echo "=== PHPUnit ==="
	@if ! $(PHP) -r \
		"define('CLI_SCRIPT',1); require '$(MOODLE_ROOT)/config.php'; \
		exit(empty(\$$CFG->phpunit_dataroot) ? 1 : 0);" 2>/dev/null; then \
		echo "SKIP: phpunit_dataroot not configured in config.php."; \
	else \
		reinit_check=$$(cd $(MOODLE_ROOT) && $(PHP) vendor/bin/phpunit \
			--testsuite $(PLUGIN_NAME)_testsuite --testdox 2>&1 | head -5); \
		if printf '%s\n' "$$reinit_check" | grep -q "initialised for different version"; then \
			echo "PHPUnit environment outdated - reinitialising..."; \
			cd $(MOODLE_ROOT) && $(PHP) admin/tool/phpunit/cli/init.php; \
		fi; \
		tmpout=$$(mktemp); \
		cd $(MOODLE_ROOT) && $(PHP) vendor/bin/phpunit \
			--testsuite $(PLUGIN_NAME)_testsuite --testdox > "$$tmpout" 2>&1; \
		phpunit_exit=$$?; \
		grep -v "^ ✔\|^ ✓\|^ ↩" "$$tmpout" || true; \
		rm -f "$$tmpout"; \
		exit $$phpunit_exit; \
	fi
