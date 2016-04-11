(function(window,undefined)
{

    function Template( context )
    {
        if( !(this instanceof Template) )
           return  new Template(context);

        context = context || window.document.body;

        if( typeof context === 'string' )
        {
            context= Breeze.trim( context );
            if( context.charAt(0) !== '<' )
                context=Breeze( context );
        }

        if( !( context instanceof Breeze ) )
        {
            context=Breeze( context );
        }

        if(  context.length < 1  )
        {
            throw new Error('context invalid');
        }

        var left="<\\?",
            right="\\?>",
            shortLeft="\\{",
            shortRight="\\}",
            splitReg= new RegExp(left+'([^'+right+']+)'+right+'|'+shortLeft+'([^'+shortRight+']+)'+shortRight,'gi'),
            jscodeReg = /(^\s*(if|for|else|do|switch|case|break|{|}))(.*)?/g,
            variable={},
            replace = function( code , flag )
            {
                code=code.replace(/(^\s+|\s+$)/g,'');
                if( code == "" )
                  return "";
                if( flag===true && code.match(jscodeReg) )
                {
                    return code+'\n';
                }
                return '___code___+="' + code.replace(/"/g, '\\"') + '";\n';
            },
            make = function(template, variable)
            {
                 var code = 'var ___code___="";\n',
                     match,cursor = 0;
                 for( var v in variable )
                 {
                     code+='var '+v+'= this["'+v+'"];\n';
                 }

                while( match = splitReg.exec(template) )
                {
                    code+=replace( template.slice(cursor, match.index) );
                    if( match[2] !==undefined )
                    {
                        code +='___code___+='+match[2].replace(/(^\s+|\s+$)/g,'') +';\n';
                    }else
                    {
                        code += replace(match[1], true);
                    }
                    cursor = match.index + match[0].length;
                }
                code += replace( template.substr(cursor, template.length - cursor) );
                code += 'return ___code___;';
                return new Function( code ).call( variable );
            }

        /**
         * 指定一个变量名的值
         * @param name
         * @param value
         * @returns {Template}
         */
        this.assign=function(name,value)
        {
            var t = typeof name;
            if( t === 'string' )
            {
                variable[name] = value;

            }else if( t === 'object' )
            {
                variable=name;
            }
            return this;
        }


        /**
         * 渲染模板视图
         * @param template
         * @param variable
         * @param flag
         * @returns {*}
         */
        this.render=function(template, data , flag )
        {
              var container = context;

              if( typeof data === 'boolean' )
              {
                  flag=data;
                  data = variable;
              }

              if( typeof data === 'string' )
              {
                  throw new Error('data invalid in Template.render');
              }

              flag = !!flag;
              if( typeof template === 'string' )
              {
                  template= Breeze.trim( template );
                  if( template.charAt(0) !== '<' )
                  {
                      template = Breeze( template , context || document);
                  }
              }

              if( template instanceof Breeze )
              {
                  container = template.parent();
                  template  = container.html();
              }

              template= Breeze.trim( template );
              if( template.charAt(0) === '<' )
              {
                  template=make(template, data );
              }

              if( !flag ) {
                  container.html( template );
                  return true;
              }
              return template;
        }


    }




    /**
     *
     * <div>
     *     {foreach data key=>item}
     *         {if key==0}
     *            <li data-key='{key}'>{item}</li>
     *         {/if}
     *     {/foreach}
     * </div>
     *
     *
     *
     */


    window.Template = Template;


})(window)

