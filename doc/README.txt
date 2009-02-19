License: See, LICENSE.txt
Installation: See, INSTALL.txt

eZ Find is a search extension for eZ Publish, providing more functionality and
better results than the default search in eZ Publish.

The main advantages of eZ Find are relevancy ranking and keyword highlighting
 the search results. The engine uses heuristics to analyze the structure of
the information and thus determine relevancy. For example, if a keyword is
found in a content object's title or in any of its short attributes,
the object will have higher relevance in the search results (as opposed to
the search term only occurring within a text block in the content object).

eZ Find also improves on the default eZ Publish search functionality in
the way it updates the search index. Search results are served from a copy
of the search index. Therefore, the search index can be updated while the
search engine continues to serve results from the copy of the most recent
index. After the index is complete, the copy of the current index is replaced
with the newest version. In addition, the search engine remembers all caching
structures from previous searches and, during indexing, updates these as well.
Therefore, the more the search engine is used, the faster it becomes.
