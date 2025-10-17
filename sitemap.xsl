<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
                xmlns:html="http://www.w3.org/1999/xhtml"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <title>XML Sitemap</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style type="text/css">
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                        font-size: 16px;
                        line-height: 1.6;
                        color: #333;
                        background: #f5f5f5;
                        padding: 0;
                        margin: 0;
                    }
                    
                    .container {
                        max-width: 1200px;
                        margin: 0 auto;
                        padding: 2rem;
                    }
                    
                    header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 3rem 0;
                        margin-bottom: 2rem;
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    }
                    
                    h1 {
                        font-size: 2.5rem;
                        font-weight: 700;
                        margin-bottom: 0.5rem;
                    }
                    
                    .subtitle {
                        font-size: 1.1rem;
                        opacity: 0.9;
                        margin-bottom: 1rem;
                    }
                    
                    .stats {
                        display: flex;
                        gap: 2rem;
                        margin-top: 1.5rem;
                        flex-wrap: wrap;
                    }
                    
                    .stat {
                        background: rgba(255,255,255,0.2);
                        padding: 0.75rem 1.5rem;
                        border-radius: 8px;
                        backdrop-filter: blur(10px);
                    }
                    
                    .stat-label {
                        font-size: 0.875rem;
                        opacity: 0.9;
                    }
                    
                    .stat-value {
                        font-size: 1.5rem;
                        font-weight: 700;
                        margin-top: 0.25rem;
                    }
                    
                    .info-box {
                        background: white;
                        border-left: 4px solid #667eea;
                        padding: 1.5rem;
                        margin-bottom: 2rem;
                        border-radius: 4px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    
                    .info-box h2 {
                        font-size: 1.25rem;
                        margin-bottom: 0.5rem;
                        color: #667eea;
                    }
                    
                    .info-box p {
                        color: #666;
                        margin-bottom: 0.5rem;
                    }
                    
                    .info-box a {
                        color: #667eea;
                        text-decoration: none;
                        font-weight: 600;
                    }
                    
                    .info-box a:hover {
                        text-decoration: underline;
                    }
                    
                    table {
                        width: 100%;
                        background: white;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        border-collapse: collapse;
                    }
                    
                    thead {
                        background: #667eea;
                        color: white;
                    }
                    
                    th {
                        text-align: left;
                        padding: 1rem;
                        font-weight: 600;
                        font-size: 0.875rem;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    tbody tr {
                        border-bottom: 1px solid #e5e7eb;
                        transition: background 0.15s ease;
                    }
                    
                    tbody tr:hover {
                        background: #f9fafb;
                    }
                    
                    tbody tr:last-child {
                        border-bottom: none;
                    }
                    
                    td {
                        padding: 1rem;
                        font-size: 0.9375rem;
                    }
                    
                    td.url {
                        font-family: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, monospace;
                        color: #667eea;
                    }
                    
                    td.url a {
                        color: inherit;
                        text-decoration: none;
                    }
                    
                    td.url a:hover {
                        text-decoration: underline;
                    }
                    
                    td.priority {
                        font-weight: 600;
                    }
                    
                    td.changefreq {
                        text-transform: capitalize;
                    }
                    
                    .badge {
                        display: inline-block;
                        padding: 0.25rem 0.75rem;
                        border-radius: 12px;
                        font-size: 0.75rem;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    .badge-high {
                        background: #d1fae5;
                        color: #065f46;
                    }
                    
                    .badge-medium {
                        background: #dbeafe;
                        color: #1e40af;
                    }
                    
                    .badge-low {
                        background: #e5e7eb;
                        color: #6b7280;
                    }
                    
                    footer {
                        margin-top: 3rem;
                        padding: 2rem 0;
                        text-align: center;
                        color: #666;
                        font-size: 0.875rem;
                    }
                    
                    @media (max-width: 768px) {
                        .container {
                            padding: 1rem;
                        }
                        
                        h1 {
                            font-size: 1.75rem;
                        }
                        
                        table {
                            font-size: 0.875rem;
                        }
                        
                        th, td {
                            padding: 0.75rem 0.5rem;
                        }
                        
                        .stats {
                            flex-direction: column;
                            gap: 1rem;
                        }
                    }
                </style>
            </head>
            <body>
                <header>
                    <div class="container">
                        <h1>🗺️ XML Sitemap</h1>
                        <p class="subtitle">This is a search engine optimized sitemap for your website.</p>
                        <div class="stats">
                            <div class="stat">
                                <div class="stat-label">Total URLs</div>
                                <div class="stat-value">
                                    <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/>
                                </div>
                            </div>
                            <div class="stat">
                                <div class="stat-label">Last Updated</div>
                                <div class="stat-value">
                                    <xsl:value-of select="substring(sitemap:urlset/sitemap:url[1]/sitemap:lastmod, 1, 10)"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
                
                <div class="container">
                    <div class="info-box">
                        <h2>📋 What is a Sitemap?</h2>
                        <p>
                            This XML Sitemap helps search engines like Google, Bing, and others discover and index all pages on this website.
                            It's automatically generated and updated whenever content changes.
                        </p>
                        <p>
                            Learn more: <a href="https://www.sitemaps.org/" target="_blank" rel="noopener">sitemaps.org</a>
                        </p>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50%">URL</th>
                                <th style="width: 20%">Priority</th>
                                <th style="width: 15%">Change Freq</th>
                                <th style="width: 15%">Last Modified</th>
                            </tr>
                        </thead>
                        <tbody>
                            <xsl:for-each select="sitemap:urlset/sitemap:url">
                                <tr>
                                    <td class="url">
                                        <a>
                                            <xsl:attribute name="href">
                                                <xsl:value-of select="sitemap:loc"/>
                                            </xsl:attribute>
                                            <xsl:value-of select="sitemap:loc"/>
                                        </a>
                                    </td>
                                    <td class="priority">
                                        <xsl:choose>
                                            <xsl:when test="sitemap:priority &gt;= 0.8">
                                                <span class="badge badge-high">
                                                    <xsl:value-of select="sitemap:priority"/>
                                                </span>
                                            </xsl:when>
                                            <xsl:when test="sitemap:priority &gt;= 0.5">
                                                <span class="badge badge-medium">
                                                    <xsl:value-of select="sitemap:priority"/>
                                                </span>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <span class="badge badge-low">
                                                    <xsl:value-of select="sitemap:priority"/>
                                                </span>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </td>
                                    <td class="changefreq">
                                        <xsl:value-of select="sitemap:changefreq"/>
                                    </td>
                                    <td>
                                        <xsl:value-of select="substring(sitemap:lastmod, 1, 10)"/>
                                    </td>
                                </tr>
                            </xsl:for-each>
                        </tbody>
                    </table>
                    
                    <footer>
                        Generated by Kirby SEO AI Plugin
                    </footer>
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
