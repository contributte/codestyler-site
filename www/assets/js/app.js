import Alpine from './3rd/alpine.js';
import { initPlayground } from './ui/playground.js';
import { initSniffBrowser } from './ui/sniff-browser.js';

initPlayground();
initSniffBrowser();

Alpine.data('layout', () => ({}));

Alpine.start();
