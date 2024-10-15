# The `<an+b>` type parsing rules

Reference: https://drafts.csswg.org/css-syntax-3/#the-anb-type

| 1st              | 2nd          | 3rd              | 4th              | 5th              | 6th              |
|------------------|--------------|------------------|------------------|------------------|------------------|
| ident/odd/       |              |                  |                  |                  |                  |
| ident/even/      |              |                  |                  |                  |                  |
| integer          |              |                  |                  |                  |                  |
| '+'?             | ident/n/     |                  |                  |                  |                  |
| '+'?             | ident/n/     | ws?              | signed-integer   |                  |                  |
| '+'?             | ident/n/     | ws?              | ['+' or '-']     | ws?              | signless-integer |
| '+'?             | ident/n-/    | ws?              | signless-integer |                  |                  |
| '+'?             | ident/n-\d+/ |                  |                  |                  |                  |
| ident/-n/        |              |                  |                  |                  |                  |
| ident/-n/        | ws?          | signed-integer   |                  |                  |                  |
| ident/-n/        | ws?          | ['+' or '-']     | ws?              | signless-integer |                  |
| ident/-n-/       | ws?          | signless-integer |                  |                  |                  |
| ident/-n-\d+/    |              |                  |                  |                  |                  |
| dimension/n/     |              |                  |                  |                  |                  |
| dimension/n/     | ws?          | signed-integer   |                  |                  |                  |
| dimension/n/     | ws?          | ['+' or '-']     | ws?              | signless-integer |                  |
| dimension/n-/    | ws?          | signless-integer |                  |                  |                  |
| dimension/n-\d+/ |              |                  |                  |                  |                  |
