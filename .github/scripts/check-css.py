#!/usr/bin/env python3
"""Parse all CSS rules and declarations, failing on syntax errors."""
import pathlib, sys, tinycss2
root=pathlib.Path(sys.argv[1] if len(sys.argv)>1 else ".")
errors=[]
def visit(nodes,path):
    for node in nodes:
        if node.type=="error":
            errors.append(f"{path}:{node.source_line}:{node.source_column}: {node.message}")
        elif node.type=="qualified-rule":
            visit(node.prelude,path)
            visit(tinycss2.parse_blocks_contents(node.content,skip_comments=True,skip_whitespace=True),path)
        elif node.type=="at-rule":
            visit(node.prelude,path)
            if node.content is None: continue
            parser=tinycss2.parse_rule_list if node.lower_at_keyword in {"media","supports","container","layer","keyframes","-webkit-keyframes","starting-style","scope"} else tinycss2.parse_blocks_contents
            visit(parser(node.content,skip_comments=True,skip_whitespace=True),path)
        elif node.type=="declaration":
            visit(node.value,path)
        elif node.type=="function":
            visit(node.arguments,path)
        elif getattr(node,"content",None) is not None:
            visit(node.content,path)
count=0
for path in root.rglob("*.css"):
    if any(part in {".git","node_modules","vendor"} for part in path.parts): continue
    count+=1
    visit(tinycss2.parse_stylesheet(path.read_text(encoding="utf-8"),skip_comments=True,skip_whitespace=True),path)
for error in errors: print("::error::"+error)
print(f"check-css: {count} stylesheet(s), {len(errors)} error(s)")
sys.exit(bool(errors))
