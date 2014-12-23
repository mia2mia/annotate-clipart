function [ str ] = niceEncode( str )

%   Allowed characters: Alphanum, '_', '-', '.', '~'; escaped by '%'

allowed_ch = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-.~';

escape_ch = '%';

bad_ch = setdiff(str,[allowed_ch escape_ch]);
bad_ch_code = dec2hex(bad_ch-0);
str = strrep(str,escape_ch,[escape_ch dec2hex(escape_ch-0) escape_ch]);
for i=1:length(bad_ch), 
    str = strrep(str,bad_ch(i),[escape_ch bad_ch_code(i,:) escape_ch]);
end

end

