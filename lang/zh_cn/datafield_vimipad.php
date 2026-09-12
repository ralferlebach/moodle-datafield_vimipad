<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Chinese (Simplified) language strings for datafield_vimipad.
 *
 * Machine-drafted starter translation for functional and RTL/CJK testing.
 * Not a certified translation; refine via AMOS. English is the source of truth.
 *
 * @package    datafield_vimipad
 * @copyright  2026 Ralf Erlebach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fieldtypelabel'] = 'ViMi Pad';
$string['mapsummary'] = '包含 {$a->nodes} 个节点和 {$a->relations} 个关系的图';
$string['noscript'] = '此字段需要启用 JavaScript 才能构建图。';
$string['pluginname'] = 'ViMi Pad';
$string['privacy:metadata'] = 'ViMi Pad 数据库字段本身不存储任何个人数据。图由“数据库”活动存储在核心管理的表中。';
$string['profile'] = '图示配置';
$string['profile_help'] = '此字段中的 ViMi Pad 条目仅限于某种图示配置，例如概念图、思维导图或树。该列表由 ViMi Pad 活动提供。';
$string['profilelocked'] = '无法更改图示配置：已有 {$a} 个条目包含图，更改后将不再与该字段匹配。请先清空这些条目，或使用所需配置添加新字段。';
