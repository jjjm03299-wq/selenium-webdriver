#!/usr/bin/env node
const { spawn } = require('child_process');

const args = process.argv.slice(2);
const command = args[0];

switch (command) {
  case 'download':
    spawn('wget', [
      '-O',
      'selenium.php',
      'https://raw.githubusercontent.com/jjjm03299-wq/selenium-webdriver/refs/heads/main/selenium.php'
    ], { stdio: 'inherit' });
    break;

  case 'run':
    spawn('php', ['selenium.php'], { stdio: 'inherit' });
    break;

  case 'help':
  case '--help':
    console.log(`
Project: selenium-webdriver-tool

Usage: selenium-webdriver-tool <command> [options]

Commands:
  download       Download selenium.php from GitHub
  run            Run selenium.php with PHP
  help, --help   Show this help message
  --version      Show version
    `);
    break;

  case '--version':
    console.log('selenium-webdriver-tool CLI version 1.0.0');
    break;

  default:
    console.log('Unknown command. Use --help for usage.');
}
