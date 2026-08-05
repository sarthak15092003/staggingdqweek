#!/usr/bin/env python3
"""
DQWeek Migration Script
Fetches all categories and posts from https://www.dqweek.com/ and pushes them to https://egz1w2tn78-staging.onrocket.site/
"""

import os
import sys
import re
import json
import time
import argparse
import urllib.request
import urllib.parse
import xml.etree.ElementTree as ET
from concurrent.futures import ThreadPoolExecutor, as_completed
from bs4 import BeautifulSoup

# Default Config
SOURCE_DOMAIN = "https://www.dqweek.com"
DEST_BRIDGE_URL = "https://egz1w2tn78-staging.onrocket.site/wp-content/themes/quanto/import_bridge.php"
SECRET_KEY = "dqweek_migration_secret_2026"
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

def fetch_url(url, timeout=15):
    req = urllib.request.Request(url, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        return resp.read().decode('utf-8', errors='ignore')

def post_bridge(action, payload):
    payload['secret_key'] = SECRET_KEY
    encoded = urllib.parse.urlencode(payload).encode('utf-8')
    url = f"{DEST_BRIDGE_URL}?action={action}"
    req = urllib.request.Request(url, data=encoded, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=45) as resp:
        return json.loads(resp.read().decode('utf-8'))

def sync_categories():
    print("=== Syncing Categories ===")
    sitemap_url = f"{SOURCE_DOMAIN}/category-sitemap.xml"
    cat_urls = []
    try:
        xml_data = fetch_url(sitemap_url)
        root = ET.fromstring(xml_data)
        cat_urls = [elem.text for elem in root.findall('.//{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]
        print(f"Found {len(cat_urls)} category URLs in sitemap.")
        
        synced_count = 0
        for loc in cat_urls:
            slug = loc.rstrip('/').split('/')[-1]
            if not slug or slug == 'dqweek.com':
                continue
            name = ucwords_slug(slug)
            res = post_bridge('create_category', {'name': name, 'slug': slug})
            if res.get('success'):
                status = "Existed" if res.get('existed') else "Created"
                print(f"  [{status}] Category: {name} (slug: {slug})")
                synced_count += 1
            else:
                print(f"  [ERROR] Category {name}: {res.get('error')}")
        print(f"Categories Sync Complete! ({synced_count} categories processed)\n")
    except Exception as e:
        print(f"[ERROR] Failed to sync categories: {e}\n")
    return cat_urls

def ucwords_slug(slug):
    return ' '.join([word.capitalize() for word in slug.replace('-', ' ').split()])

def discover_all_post_urls(sitemaps_limit=50, category_urls=[]):
    print("=== Discovering Post URLs Across Site ===")
    all_urls = set()

    # 1. Scrape Homepage links
    try:
        print("1. Extracting post URLs from Homepage...")
        hp_html = fetch_url(SOURCE_DOMAIN)
        soup = BeautifulSoup(hp_html, 'html.parser')
        for a in soup.find_all('a', href=True):
            href = a['href']
            if re.search(r'/(news|products|enterprise|smb|software|gadgets|security-surveillance|south|west|east|north|national|editorial-columns|retail-ratna)/[a-z0-9\-]+-\d+', href):
                full_url = href if href.startswith('http') else SOURCE_DOMAIN + ('' if href.startswith('/') else '/') + href
                all_urls.add(full_url)
        print(f"   -> Found {len(all_urls)} post URLs on Homepage.")
    except Exception as e:
        print(f"   -> [WARN] Homepage scrape error: {e}")

    # 2. Scrape Category Pages
    if category_urls:
        print("2. Extracting post URLs from Category Pages...")
        cat_found = 0
        for cat_url in category_urls[:20]: # Check top 20 category landing pages
            try:
                cat_html = fetch_url(cat_url)
                soup = BeautifulSoup(cat_html, 'html.parser')
                for a in soup.find_all('a', href=True):
                    href = a['href']
                    if re.search(r'/[a-z0-9\-]+-\d+$', href) and not href.endswith('.xml'):
                        full_url = href if href.startswith('http') else SOURCE_DOMAIN + ('' if href.startswith('/') else '/') + href
                        if full_url not in all_urls:
                            all_urls.add(full_url)
                            cat_found += 1
            except Exception as e:
                pass
        print(f"   -> Found {cat_found} additional post URLs from Category Pages.")

    # 3. Scrape Sitemap files
    main_sitemap_url = f"{SOURCE_DOMAIN}/sitemap.xml"
    try:
        print(f"3. Scanning Sitemap index files (limit={sitemaps_limit if sitemaps_limit > 0 else 'ALL'})...")
        xml_data = fetch_url(main_sitemap_url)
        root = ET.fromstring(xml_data)
        sitemap_files = [elem.text for elem in root.findall('.//{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]
        
        target_sitemaps = sitemap_files if sitemaps_limit <= 0 else sitemap_files[:sitemaps_limit]
        print(f"   -> Scanning {len(target_sitemaps)} sitemap files...")
        
        sitemap_count = 0
        for idx, sm_url in enumerate(target_sitemaps, 1):
            try:
                sm_xml = fetch_url(sm_url)
                sm_root = ET.fromstring(sm_xml)
                urls = [elem.text for elem in sm_root.findall('.//{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]
                post_urls = [u for u in urls if not u.endswith('llms.txt') and not u.endswith('.xml')]
                for u in post_urls:
                    if u not in all_urls:
                        all_urls.add(u)
                        sitemap_count += 1
            except Exception as e:
                pass
        print(f"   -> Found {sitemap_count} additional post URLs from Sitemaps.")
    except Exception as e:
        print(f"   -> [WARN] Sitemap index error: {e}")

    url_list = sorted(list(all_urls))
    print(f"Total Unique Post URLs Discovered: {len(url_list)}\n")
    return url_list

def scrape_and_import_post(post_url):
    try:
        html = fetch_url(post_url)
        soup = BeautifulSoup(html, 'html.parser')
        
        # Extract title
        og_title = soup.find('meta', property='og:title')
        title = og_title['content'] if og_title else (soup.title.string if soup.title else '')
        title = title.replace(' - DQWeek', '').replace(' | DQWeek', '').strip()
        
        if not title:
            return {'url': post_url, 'success': False, 'error': 'No title found'}
            
        slug = post_url.rstrip('/').split('/')[-1]
        
        # Featured Image
        og_image = soup.find('meta', property='og:image')
        image_url = og_image['content'] if og_image else ''
        
        # Category & Date from JSON-LD or meta or DOM
        category_slug = ''
        publish_date = ''
        
        for s in soup.find_all('script', type='application/ld+json'):
            if s.string and any(k in s.string for k in ['NewsArticle', 'BlogPosting', 'Article']):
                try:
                    ld = json.loads(s.string)
                    if isinstance(ld, dict):
                        publish_date = ld.get('datePublished', '')
                        if not image_url and 'image' in ld:
                            img = ld['image']
                            image_url = img[0] if isinstance(img, list) else (img.get('url') if isinstance(img, dict) else img)
                        break
                except:
                    pass
                    
        # Extract category & DOM fallback
        main_div = soup.find('div', class_=lambda c: c and 'article_main_div' in c)
        if main_div:
            category_slug = main_div.get('data-page-primary-category', '')
            
        if not category_slug:
            parts = post_url.replace(SOURCE_DOMAIN, '').strip('/').split('/')
            if len(parts) > 1:
                category_slug = parts[0]

        # Content extraction
        article = soup.find('article')
        content_html = ''
        if article:
            for bad_tag in article.find_all(['script', 'style', 'iframe', 'ins', 'form']):
                bad_tag.decompose()
            content_html = ''.join(str(c) for c in article.contents)
            
        if not content_html:
            main_content = soup.find('div', class_=lambda c: c and ('content' in c or 'story' in c))
            if main_content:
                content_html = str(main_content)
                
        payload = {
            'title': title,
            'slug': slug,
            'content': content_html,
            'date': publish_date,
            'category': category_slug,
            'image_url': image_url
        }
        
        res = post_bridge('create_post', payload)
        if res.get('success'):
            status = "Existed" if res.get('existed') else "Created"
            return {'url': post_url, 'title': title, 'success': True, 'status': status, 'post_id': res.get('post_id')}
        else:
            return {'url': post_url, 'title': title, 'success': False, 'error': res.get('error')}
    except Exception as e:
        return {'url': post_url, 'success': False, 'error': str(e)}

def main():
    parser = argparse.ArgumentParser(description="Migrate categories and posts from dqweek.com to staging site.")
    parser.add_argument('--limit', type=int, default=0, help="Max number of posts to process (0 = process all discovered)")
    parser.add_argument('--sitemaps', type=int, default=50, help="Max sitemap files to scan (0 = scan ALL 3,300+ sitemaps, default 50)")
    parser.add_argument('--threads', type=int, default=8, help="Number of concurrent migration threads (default 8)")
    parser.add_argument('--categories-only', action='store_true', help="Sync categories only")
    args = parser.parse_args()

    # Step 1: Sync Categories
    category_urls = sync_categories()
    if args.categories_only:
        print("Categories-only mode completed.")
        return

    # Step 2: Discover Post URLs across Homepage, Categories & Sitemaps
    post_urls = discover_all_post_urls(sitemaps_limit=args.sitemaps, category_urls=category_urls)
    if not post_urls:
        print("No post URLs found. Exiting.")
        return

    if args.limit > 0:
        post_urls = post_urls[:args.limit]

    print(f"=== Starting Migration of {len(post_urls)} Posts (threads={args.threads}) ===")
    start_time = time.time()
    success_count = 0
    existed_count = 0
    fail_count = 0

    with ThreadPoolExecutor(max_workers=args.threads) as executor:
        futures = {executor.submit(scrape_and_import_post, url): url for url in post_urls}
        for i, future in enumerate(as_completed(futures), 1):
            res = future.result()
            if res.get('success'):
                if res.get('status') == 'Existed':
                    existed_count += 1
                else:
                    success_count += 1
                print(f"[{i}/{len(post_urls)}] [{res['status']}] Post ID {res.get('post_id')}: {res['title']}")
            else:
                fail_count += 1
                print(f"[{i}/{len(post_urls)}] [FAILED] {res.get('url')}: {res.get('error')}")

    elapsed = time.time() - start_time
    print(f"\n==========================================")
    print(f" Migration Summary:")
    print(f"  - Total Posts Processed: {len(post_urls)}")
    print(f"  - Successfully Created:  {success_count}")
    print(f"  - Already Existed:       {existed_count}")
    print(f"  - Failed:                {fail_count}")
    print(f"  - Time Elapsed:          {elapsed:.2f} seconds")
    print(f"==========================================\n")

if __name__ == '__main__':
    main()
