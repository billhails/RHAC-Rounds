# Hover

This is a fork of the Hover plugin by Stefan V&ouml;lkel.

It recognises any of a set of words in a dictionary and
wraps them in a span with popup help text describing the
word.

We use it for describing rounds.

The original had issues with substrings, i.e. it would match
the "Metric" in "Metric III" then fail to match "Metric III"

This version orders the words by length.