# The `<an+b>` type parsing rules

Reference: https://drafts.csswg.org/css-syntax-3/#the-anb-type

```
<number>(int)
<ident>(/odd/)
<ident>(/even/)
<ident>(/-n/)
<ident>(/-n/)        <ws>?<number>(int,signed)
<ident>(/-n/)        <ws>?['+'|'-']              <ws>?<number>(int,signless)
<ident>(/-n-/)       <ws>?<number>(int,signless)
<ident>(/-n-\d+/)
<dimension>(/n/)
<dimension>(/n/)     <ws>?<number>(int,signed)
<dimension>(/n/)     <ws>?['+'|'-']              <ws>?<number>(int,signless)
<dimension>(/n-/)    <ws>?<number>(int,signless)
<dimension>(/n-\d+/)
'+'                  <ident>(/n/)
'+'                  <ident>(/n/)                <ws>?<number>(int,signed)
'+'                  <ident>(/n/)                <ws>?['+'|'-']              <ws>?<number>(int,signless)
'+'                  <ident>(/n-/)               <ws>?<number>(int,signless)
'+'                  <ident>(/n-\d+/)
```