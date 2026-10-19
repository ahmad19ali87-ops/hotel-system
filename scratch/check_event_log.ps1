Get-EventLog -LogName Application -Newest 10 -EntryType Error,Warning | Format-List TimeGenerated, Source, Message

